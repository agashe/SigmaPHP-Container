<?php

namespace SigmaPHP\Container\Tests\Examples;

use SigmaPHP\Container\Tests\Examples\Mailer;

class Notification
{
    /**
     * @var Mailer $mailer
     */
    protected $mailer;

    /**
     * Set mailer service.
     * 
     * @param Mailer $mailer
     * @return void
     */
    public function setMailer(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Push notification to user using specific mail service.
     * 
     * @param Mailer $mailer
     * @param string $name
     * @param string $email
     * @return void
     */
    public function pushMessageUsingMailer(
        Mailer $mailer, 
        $name, 
        $email = 'testing@example.com'
    ) {
        $mailer->send(
            $email,
            "Notification using mailer to : \"{$name}\""
        );
    }

    /**
     * Push notification to user.
     * 
     * @param string $name
     * @param string $email
     * @return void
     */
    public function pushMessage($name, $email)
    {
        $this->mailer->send(
            $email,
            "Notification to : \"{$name}\""
        );
    }
}