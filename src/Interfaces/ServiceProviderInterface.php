<?php

namespace SigmaPHP\Container\Interfaces;

use SigmaPHP\Container\Container;

/**
 * Service Provider Interface
 */
interface ServiceProviderInterface
{
    /**
     * The boot method , will be called after all 
     * dependencies were defined in the container.
     * 
     * @param Container $container
     * @return void
     */
    public function boot(Container $container);

    /**
     * Add a definition to the container.
     * 
     * @param Container $container
     * @return void
     */
    public function register(Container $container);
}