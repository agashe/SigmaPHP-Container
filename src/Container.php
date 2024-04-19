<?php

namespace SigmaPHP\Container;

use SigmaPHP\Container\Interfaces\ContainerInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

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
     * Get an instance for a definition from the container.
     * 
     * @param string $id
     * @return mixed 
     */
    public function get($id)
    {
        return (new ('\\' . $this->dependencies[$id]));
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
        $this->dependencies[$id] = $definition;
        return $this;
    }

    /**
     * Check that the id is valid.
     * 
     * @param string $id
     * @return void
     */
    public function validateId($id)
    {

    }

    /**
     * Check that the definition is valid.
     * 
     * @param string $definition
     * @return void
     */
    public function validateDefinition($definition)
    {

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