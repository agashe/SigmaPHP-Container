<?php

namespace SigmaPHP\Container\Tests\Examples;

use SigmaPHP\Container\Interfaces\ProviderInterface;
use SigmaPHP\Container\Container;
use SigmaPHP\Container\Tests\Examples\Mailer as MailerExample;
use SigmaPHP\Container\Tests\Examples\User as UserExample;

class UserServiceProvider implements ProviderInterface
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
        $user->email = 'mohamed@eample.com';
        
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