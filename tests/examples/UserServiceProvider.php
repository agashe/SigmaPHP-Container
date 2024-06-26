<?php

namespace SigmaPHP\Container\Tests\Examples;

use SigmaPHP\Container\Container;
use SigmaPHP\Container\Interfaces\ServiceProviderInterface;
use SigmaPHP\Container\Tests\Examples\Mailer as MailerExample;
use SigmaPHP\Container\Tests\Examples\User as UserExample;

class UserServiceProvider implements ServiceProviderInterface
{
    /**
     * The boot method , will be called after all 
     * dependencies were defined in the container.
     * 
     * @param Container $container
     * @return void
     */
    public function boot(Container $container)
    {
        $user = $container->get(UserExample::class);
        
        $user->name = 'mohamed';
        $user->email = 'mohamed@example.com';
        
        $user->sendWelcomeMail();
    }

    /**
     * Add a definition to the container.
     * 
     * @param Container $container
     * @return void
     */
    public function register(Container $container)
    {
        $container->set(MailerExample::class);

        $container->set(UserExample::class)
            ->setParam(MailerExample::class);
    }
}