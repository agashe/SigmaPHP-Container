<?php

namespace SigmaPHP\Container\Tests\Examples;

use SigmaPHP\Container\Tests\Examples\Mailer;
use SigmaPHP\Container\Tests\Examples\MarketingMailer;

class SuperAdmin extends User
{
    
    /**
     * Super Admin Constructor
     * 
     * @param MarketingMailer|Mailer $mailer
     */
    public function __construct(MarketingMailer|Mailer $mailer)
    {
        $this->mailer = $mailer;
    }
}