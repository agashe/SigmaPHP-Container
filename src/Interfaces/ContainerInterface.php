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
     * Register a service provider.
     * 
     * @param string $provider
     * @return void
     */
    public function registerProvider($provider);
    
    /**
     * Register an array of service providers.
     * 
     * @param array $providers
     * @return void
     */
    public function registerProviders($providers);

    /**
     * Make new instance of definition.
     * 
     * @param string $id
     * @return mixed
     */
    public function make($id);
    
    /**
     * Call a method in class.
     * 
     * @param string $id
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function call($id, $method, $args);
    
    /**
     * Call a closure and inject all necessary dependencies.
     * 
     * @param \Closure $closure
     * @param array $args
     * @return mixed
     */
    public function callFunction($closure, $args);
    
    /**
     * Enable autowiring.
     * 
     * @return void
     */
    public function autowire();
}