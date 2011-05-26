<?php

namespace Liip\FunctionalTestBundle\Test\Mailer;

use Liip\FunctionalTestBundle\Test\Mailer\SwiftMessageMock;

class SwiftMailerMock
{
    protected $mail_path;
    protected $message;
  
    public function __construct($mail_path = '/tmp/mail')
    {
        $this->mail_path = $mail_path;
    }

    public function createMessage()
    {
        return new SwiftMessageMock();
    }

    public function send($message)
    {

        \file_put_contents($this->getAbsoluteMessagePath(join('.', \array_flip($message->getTo()))), \serialize($message));
    }

    public function getMessage($to)
    {
        return \unserialize(\file_get_contents($this->getAbsoluteMessagePath($to)));
    }

    public function getAbsoluteMessagePath($to)
    {
        return $this->getAbsoluteMailPath().\DIRECTORY_SEPARATOR.$to.'.mail';
    }

    protected function getAbsoluteMailPath()
    {
        return \realpath($this->mail_path);
    }
}
