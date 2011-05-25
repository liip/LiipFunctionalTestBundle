<?php

namespace Liip\FunctionalTestBundle\Tests\MailerMock;

use Liip\FunctionalTestBundle\Test\Mailer\SwiftMailerMock;

class SwiftMailerMockTest extends \PHPUnit_Framework_TestCase
{
  protected $mailer;

  public function setUp()
  {
    $this->mailer = new SwiftMailerMock();
  }

  public function test_getAbsoluteMessagePath()
  {
    $this->assertEquals(realpath('/tmp/mail/').'/cirpo@example.com.mail', $this->mailer->getAbsoluteMessagePath('cirpo@example.com'));
  }

  public function test_send()
  {
    $message = $this->getMock('Message', array('getTo'));
    $message->expects($this->once())
            ->method('getTo')
            ->will($this->returnValue(array('bye@example.com' => 'Ciao')));

    $this->mailer->send($message);

    $this->assertEquals($message, $this->mailer->getMessage('bye@example.com'));
  }
}
