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
     * Set a parameter or bind it to a definition.
     * 
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function setParam($name, $value);

    /**
     * Get unbounded parameter's value.
     * 
     * @param string $name
     * @return self
     */
    public function getParam($name);

    /**
     * Bind a method to a definition.
     * 
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function setMethod($name, $value);
}