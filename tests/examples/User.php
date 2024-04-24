<?php

namespace SigmaPHP\Container\Tests\Examples;

use SigmaPHP\Container\Tests\Examples\Mailer;

class User
{
    public $name;
    public $email;
    protected $mailer;

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendWelcomeMail()
    {
        $this->mailer->send(
            $this->email,
            "Hello \"{$this->name}\""
        );
    }
}