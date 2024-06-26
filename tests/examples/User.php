<?php

namespace SigmaPHP\Container\Tests\Examples;

use SigmaPHP\Container\Tests\Examples\Mailer;

class User
{
    /**
     * @var string $name
     */
    public $name;

    /**
     * @var string $email
     */
    public $email;

    /**
     * @var Mailer $mailer
     */
    protected $mailer;

    /**
     * User Constructor
     * 
     * @param Mailer $mailer
     */
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Send welcome mail to user's address.
     * 
     * @return void
     */
    public function sendWelcomeMail()
    {
        $this->mailer->send(
            $this->email,
            "Hello \"{$this->name}\""
        );
    }
}