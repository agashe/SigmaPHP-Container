<?php

namespace SigmaPHP\Container\Interfaces;

/**
 * Container Interface
 */
interface ContainerInterface
{
    /**
     * Add new definition for a class to the container.
     * 
     * @param string $id
     * @param mixed $definition
     * @return self 
     */
    public function set($id, $definition);

    /**
     * Check that the id is valid.
     * 
     * @param string $id
     * @return void
     */
    public function validateId($id);

    /**
     * Check that the definition is valid.
     * 
     * @param mixed $definition
     * @return void
     */
    public function validateDefinition($definition);

    /**
     * Bind a parameter to a definition.
     * 
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function param($name, $value);

    /**
     * Bind a method to a definition.
     * 
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function method($name, $value);
}