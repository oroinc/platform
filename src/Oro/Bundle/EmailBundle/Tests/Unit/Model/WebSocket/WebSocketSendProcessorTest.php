<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model\WebSocket;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Model\WebSocket\WebSocketSendProcessor;

class WebSocketSendProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $topicPublisher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $email;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $user;

    /**
     * @var WebSocketSendProcessor
     */
    protected $processor;

    protected function setUp()
    {
        $emailId = 10;
        $userId = 20;

        $this->topicPublisher = $this->getMockBuilder('Oro\Bundle\SyncBundle\Wamp\TopicPublisher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->email = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')
            ->disableOriginalConstructor()
            ->getMock();

        $this->user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $this->email->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue($emailId));

        $this->user->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue($userId));

        $this->processor = new WebSocketSendProcessor($this->topicPublisher);
    }

    public function testSendSuccess()
    {
        $this->topicPublisher->expects($this->once())
            ->method('send')
            ->with(
                sprintf(WebSocketSendProcessor::TOPIC, $this->user->getId()),
                json_encode([['email_id' => $this->email->getId()]])
            );

        $emailUser = new EmailUser();
        $emailUser->setEmail($this->email);
        $emailUser->setOwner($this->user);

        $this->processor->send($emailUser);
    }
}
