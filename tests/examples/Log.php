<?php

namespace SigmaPHP\Container\Tests\Examples;

use SigmaPHP\Container\Tests\Examples\Mailer;

class Log
{
    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var string $email
     */
    protected $email;

    
    /**
     * @var Mailer $mailer
     */
    protected $mailer;

    /**
     * Set mailer service, and the responsible admin.
     * 
     * @param Mailer $mailer
     * @param string $name
     * @param string $email
     * @return void
     */
    public function setMailerAndAdmin(Mailer $mailer, $name, $email)
    {
        $this->mailer = $mailer;
        $this->name = $name;
        $this->email = $email;
    }

    /**
     * Send alert to admin.
     * 
     * @return void
     */
    public function sendAlert()
    {
        $this->mailer->send(
            $this->email,
            "Alert to : \"{$this->name}\""
        );
    }
}