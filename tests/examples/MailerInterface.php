<?php

namespace SigmaPHP\Container\Tests\Examples;

interface MailerInterface
{
    /**
     * Send mai lto an address.
     * 
     * @param string $email
     * @param string $body
     * @return void
     */
    public function send($email, $body);
}