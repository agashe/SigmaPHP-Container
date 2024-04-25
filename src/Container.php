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

            $dependencyParams = isset($this->params[$id]) ?
                $this->params[$id] : [];

            if ($constructor !== null) {
                $constructorParams = [];
                
                // loop throw all args , and inject dependencies
                foreach ($constructor->getParameters() as $parameter) {
                    // check if parameter is a primitive or a class !!
                    if ($parameter->getType() !== null) {
                        $dependency = $parameter->getType()->getName();
                        
                        if (isset($dependencyParams[$parameter->name])) {
                            if (is_string(
                                    $dependencyParams[$parameter->name]
                                ) &&
                                class_exists(
                                    $dependencyParams[$parameter->name]
                                )
                            ) {
                                $constructorParams[] = $this->get($dependency);
                            } else {
                                $dependencyParam = 
                                    $dependencyParams[$parameter->name];

                                if (is_callable($dependencyParam) &&
                                    ($dependencyParam instanceof \Closure)
                                ) {
                                    // check if factory accept the container 
                                    // as a parameter
                                    $function = new \ReflectionFunction(
                                        $dependencyParam
                                    );
                                    
                                    if ($function->getParameters() !== null) {
                                        $result = $dependencyParam($this);
                                    } else {
                                        $result = $dependencyParam();
                                    }
                        
                                    $constructorParams[] = $result;
                                } else {
                                    $constructorParams[] = 
                                        $dependencyParams[$parameter->name];
                                }
                            }
                        } else {
                            $constructorParams[] = $this->get($dependency);
                        }
                    } else {
                        if (isset($dependencyParams[$parameter->name])) {
                            $constructorParams[] = 
                                $dependencyParams[$parameter->name];
                        }
                    }
                }

                $instance = $class->newInstanceArgs($constructorParams);
            } else {
                $instance = $class->newInstance();
            }

            // call any setter methods related to the definition
            if (isset($this->methods[$id]) && 
                (count($this->methods[$id]) > 0)
            ) {
                foreach ($this->methods[$id] as $name => $args) {
                    $method = new \ReflectionMethod($id, $name);
                    
                    if ($method->getParameters() !== null) {
                        $methodArgs = [];

                        foreach ($method->getParameters() as $parameter) {
                            // check if parameter is a primitive or a class !!
                            if ($parameter->getType() !== null) {
                                $dependency = $parameter->getType()->getName();
                                
                                if (isset($args[$parameter->name])) {
                                    if (is_string($args[$parameter->name]) &&
                                        class_exists($args[$parameter->name])
                                    ) {
                                        $methodArgs[] = $this->get($dependency);
                                    } else {
                                        // !! Must Be Refactored !!
                                        $methodArg = $args[$parameter->name];

                                        if (is_callable($methodArg) &&
                                            ($methodArg instanceof \Closure)
                                        ) {
                                            // check if factory accept the  
                                            // container as a parameter
                                            $function = new \ReflectionFunction(
                                                $methodArg
                                            );
                                            
                                            if ($function->getParameters() 
                                                !== null
                                            ) {
                                                $result = $methodArg($this);
                                            } else {
                                                $result = $methodArg();
                                            }
                                
                                            $methodArgs[] = $result;
                                        } else {
                                            $methodArgs[] =
                                                $args[$parameter->name];
                                        }
                                    }
                                }
                            } else {
                                if (isset($args[$parameter->name])) {
                                    $methodArgs[] = $args[$parameter->name];
                                }
                            }
                        }

                        $method->invoke($instance, ...$methodArgs);
                    } else {
                        $method->invoke($instance);
                    }
                }
            }

            $this->dependencies[$id] = $instance;
        }
        
        $definition = $this->dependencies[$id];

        if (is_callable($definition) && ($definition instanceof \Closure)) {
            // check if factory accept the container as a parameter
            $function = new \ReflectionFunction($definition);
            
            if ($function->getParameters() !== null) {
                $result = $definition($this);
            } else {
                $result = $definition();
            }

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
     * @param mixed $values
     * @return self
     */
    public function setMethod($name, $values)
    {   
        if (empty($this->dependencies) ||
            !class_exists(array_key_last($this->dependencies))
        ) {
            throw new ContainerException(
                "The method \"{$name}\" should be bounded to a class !"
            );
        }
        
        $this->methods[array_key_last($this->dependencies)][$name] = $values;

        return $this;       
    }
}