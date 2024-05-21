<?php

namespace SigmaPHP\Container\Tests\Examples;

use SigmaPHP\Container\Container;
use SigmaPHP\Container\Interfaces\ServiceProviderInterface;
use SigmaPHP\Container\Tests\Examples\Mailer as MailerExample;

class MailerServiceProvider implements ServiceProviderInterface
{
    /**
     * The boot method , will be called after all 
     * dependencies were defined in the container.
     * 
     * @param Container $container
     * @return void
     */
    public function boot(Container $container)
    {}

    /**
     * Add a definition to the container.
     * 
     * @param Container $container
     * @return void
     */
    public function register(Container $container)
    {
        $container->set(MailerExample::class);
    }
}