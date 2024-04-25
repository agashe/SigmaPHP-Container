<?php

namespace SigmaPHP\Container\Tests\Examples;

use SigmaPHP\Container\Tests\Examples\Mailer;

class Admin extends User
{
    /**
     * Admin Constructor
     * 
     * @param Mailer $mailer
     * @param string $name
     * @param string $email
     */
    public function __construct(Mailer $mailer, $name, $email)
    {
        parent::__construct($mailer);
        $this->name = $name;
        $this->email = $email;
    }
}