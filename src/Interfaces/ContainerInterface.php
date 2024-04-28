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
     * Bind a parameter to a definition.
     * 
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function setParam($name, $value);

    /**
     * Bind a method to a definition.
     * 
     * @param string $name
     * @param mixed $args
     * @return self
     */
    public function setMethod($name, $args);

    /**
     * Set a constant value in the container.
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setValue($name, $value);

    /**
     * Get a constant value from in the container.
     * 
     * @param string $name
     * @return mixed
     */
    public function getValue($name);
}