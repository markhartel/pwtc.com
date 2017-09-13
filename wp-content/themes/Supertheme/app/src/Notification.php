<?php
namespace App;

use Swift_Mailer;
use Swift_MailTransport;
use Swift_Message;

class Notification
{
    protected $from_email = "wordpress@pwtc.com";
    protected $from_name = "pwtc.com";
    protected $membership_captain_email;
    protected $membership_captain_name;
    protected $mailer;

    public function __construct($from_email, $from_name, $captain_email, $captain_name, Swift_Mailer $mailer)
    {
        $this->from_email = $from_email;
        $this->from_name = $from_name;
        $this->membership_captain_email = $captain_email;
        $this->membership_captain_name = $captain_name;
        $this->mailer = $mailer;
    }

    public function send($subject, $message)
    {
        $message = Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom(array($this->from_email => $this->from_name))
            ->setTo(array($this->membership_captain_email => $this->membership_captain_name))
            ->setBody($message, 'text/html');
        return $this->mailer->send($message);
    }
}