<?php

namespace SigmaPHP\Container\Tests\Examples;

class Mailer
{
    /**
     * Send mai lto an address.
     * 
     * @param string $email
     * @param string $body
     * @return void
     */
    public function send($email, $body)
    {
        echo "The message {$body} was sent to {$email}" . PHP_EOL;
    }
}