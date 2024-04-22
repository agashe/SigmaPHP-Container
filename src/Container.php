<?php

namespace SigmaPHP\Container;

use Closure;
use SigmaPHP\Container\Interfaces\ContainerInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use SigmaPHP\Container\Exceptions\ContainerException;
use SigmaPHP\Container\Exceptions\IdNotFoundException;

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
    public function set($id, $definition)
    {
        $this->validateId($id);
        $this->validateDefinition($definition);

        // in case of class we create new object and save it
        if (is_string($definition)) {
            $this->dependencies[$id] = (new ('\\' . $definition));
        } else {
            $this->dependencies[$id] = $definition;
        }
        
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
    public function validateId($id)
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
    public function validateDefinition($definition)
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
     * Bind a parameter to a definition.
     * 
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function param($name, $value)
    {

    }

    /**
     * Bind a method to a definition.
     * 
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function method($name, $value)
    {

    }
}