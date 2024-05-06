<?php

namespace SigmaPHP\Container\Tests\Examples;

use SigmaPHP\Container\Interfaces\ProviderInterface;
use SigmaPHP\Container\Container;
use SigmaPHP\Container\Tests\Examples\Mailer as MailerExample;
use SigmaPHP\Container\Tests\Examples\Log as LogExample;

class LogServiceProvider implements ProviderInterface
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
    {
        $container->set(LogExample::class)
            ->setMethod('setMailerAndAdmin', [
                'mailer' => MailerExample::class,
                'name' => 'admin2', 
                'email' => 'admin2@example.com'
            ]);
    }
}