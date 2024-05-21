<?php

namespace SigmaPHP\Container\Tests\Examples;

use SigmaPHP\Container\Container;
use SigmaPHP\Container\Interfaces\ServiceProviderInterface;
use SigmaPHP\Container\Tests\Examples\Mailer as MailerExample;
use SigmaPHP\Container\Tests\Examples\Log as LogExample;

class LogServiceProvider implements ServiceProviderInterface
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
        $container->set(LogExample::class)
            ->setMethod('setMailerAndAdmin', [
                'mailer' => MailerExample::class,
                'name' => $container->get('admin_name'), 
                'email' => $container->get('admin_email')
            ]);

        $logService = $container->get(LogExample::class);

        $logService->sendAlert();
    }

    /**
     * Add a definition to the container.
     * 
     * @param Container $container
     * @return void
     */
    public function register(Container $container)
    {}
}