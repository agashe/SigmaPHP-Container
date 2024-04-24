<?php

namespace SigmaPHP\Container;

use Closure;
use SigmaPHP\Container\Interfaces\ContainerInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use SigmaPHP\Container\Exceptions\ContainerException;
use SigmaPHP\Container\Exceptions\IdNotFoundException;
use SigmaPHP\Container\Exceptions\ParameterNotFoundException;

/**
 * Container Class
 */
class Container implements PsrContainerInterface , ContainerInterface
{
    /**
     * @var array $dependencies 
     */
    protected $dependencies = [];

    /**
     * @var array $params 
     */
    protected $params = [];

    /**
     * @var array $methods 
     */
    protected $methods = [];

    /**
     * Get an instance for a definition from the container.
     * 
     * @param string $id
     * @return mixed 
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            throw new IdNotFoundException(
                "The id \"{$id}\" is not found in the container !"
            );
        }

        // in case of objects , we save the object for future use
        // this is like a cache mechanism , instead of creating 
        // a new instance every time !
        if (is_string($this->dependencies[$id])) {
            $class = new \ReflectionClass($this->dependencies[$id]);
            $constructor = $class->getConstructor();
            $instance = null;

            $dependencyParameters = isset($this->params[$id]) ?
                $this->params[$id] : [];

            if ($constructor !== null) {
                $constructorParams = [];
                
                // loop throw all args , and inject dependencies
                foreach ($constructor->getParameters() as $parameter) {
                    // check if parameter is a primitive or a class !!
                    if ($parameter->getType() !== null) {
                        $dependencyName = $parameter->getType()->getName();

                        if (isset($dependencyParameters[$dependencyName])) {
                            $constructorParams[] = $this->get($dependencyName);
                        }
                    } else {
                        if (isset($dependencyParameters[$parameter->name])) {
                            $constructorParams[] = 
                                $dependencyParameters[$parameter->name];
                        }
                    }
                }

                $instance = $class->newInstanceArgs($constructorParams);
            } else {
                $instance = $class->newInstance();
            }

            $this->dependencies[$id] = $instance;
        }
        
        $definition = $this->dependencies[$id];

        if (is_callable($definition) && ($definition instanceof \Closure)) {
            $result = $definition();
            return $result;
        }
        
        return $definition;
    }

    /**
     * Check if a definition exists in the container.
     * 
     * @param string $id
     * @return bool
     */
    public function has($id): bool
    {
        return in_array($id, array_keys($this->dependencies));
    }

    /**
     * Add new definition for a class to the container.
     * 
     * @param string $id
     * @param mixed $definition
     * @return self 
     */
    public function set($id, $definition = null)
    {
        if (empty($definition)) {
            $definition = $id;
        }

        $this->validateId($id);
        $this->validateDefinition($definition);
        
        $this->dependencies[$id] = $definition;
        
        return $this;
    }

    /**
     * Check that the id is valid.
     * 
     * We have 3 valid types of ids so far :
     * - class path (e.g, Mailer::class) 
     * - interface path (e.g, MailerInterface::class) 
     * - string alias (e.g, 'mailer') 
     * 
     * @param string $id
     * @return void
     */
    protected function validateId($id)
    {
        if (!is_string($id) || empty($id)) {
            throw new ContainerException(
                "Invalid id. Id can only accept string values !"
            );
        }
    }

    /**
     * Check that the definition is valid.
     * 
     * We have 3 valid types of definitions so far :
     * - class path (e.g, Mailer::class) 
     * - callback functions "factories" (e.g, fn() => {...}) 
     * - objects (e.g, new Mailer()) 
     * 
     * @param string $definition
     * @return void
     */
    protected function validateDefinition($definition)
    { 
        $invalid = false;

        if (is_string($definition)) {
            if (empty($definition) || !class_exists('\\' . $definition)) {
                $invalid = true;
            }
        } else {
            if (!is_callable($definition) && !is_object($definition)) {
                $invalid = true;
            }
        }
        
        if ($invalid) {
            throw new ContainerException(
                "Invalid definition : " .
                "only classes , objects and callbacks are accepted !"
            );
        }
    }

    /**
     * Set a parameter or bind it to a definition.
     * 
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function setParam($name, $value = null)
    {
        if (empty($value)) {
            $value = $name;

            // validate that the parameter is a valid class path
            $this->validateDefinition($value);
        }

        if (!empty($this->dependencies)) {
            $this->params[array_key_last($this->dependencies)][$name] = $value;
        } else {
            $this->params[$name] = $value;
        }

        return $this;
    }

     /**
     * Get unbounded parameter's value.
     * 
     * @param string $name
     * @return mixed
     */
    public function getParam($name)
    {
        if (!in_array($name, array_keys($this->params))) {
            throw new ParameterNotFoundException(
                "The parameter \"{$name}\" is not found in the container !"
            );
        }

        return $this->params[$name];
    }

    /**
     * Bind a method to a definition.
     * 
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function setMethod($name, $value)
    {

    }
}