<?php

namespace SigmaPHP\Container\Tests\Examples;

use SigmaPHP\Container\Tests\Examples\MailerInterface;

class Customer
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
     * Customer Constructor
     * 
     * @param Mailer $mailer
     */
    public function __construct(MailerInterface $mailer)
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
            "Hi , Customer \"{$this->name}\""
        );
    }
}