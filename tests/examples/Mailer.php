<?php

namespace SigmaPHP\Container\Tests\Examples;

class Mailer
{
    public function send($email, $body)
    {
        echo "The message {$body} was sent to {$email}" . PHP_EOL;
    }
}