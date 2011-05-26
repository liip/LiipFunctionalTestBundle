<?php

namespace Liip\FunctionalTestBundle\Test\Mailer;

class SwiftMessageMock
{
    protected $subject;
    protected $from;
    protected $to;
    protected $body;

    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    public function setTo($to)
    {
        $this->to = $to;

        return $this;
    }

    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function getTo()
    {
        return $this->to;
    }

    public function getBody()
    {
        return $this->body;
    }

}
