<?php
namespace Oro\Component\Messaging\Tests\ZeroConfig;

use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\Transport\MessageProducer;
use Oro\Component\Messaging\Transport\Topic;
use Oro\Component\Messaging\ZeroConfig\FactoryInterface;
use Oro\Component\Messaging\ZeroConfig\ZeroConfig;

class ZeroConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructed()
    {
        new ZeroConfig($this->createFactoryMock());
    }
    
    public function testShouldSendMessage()
    {
        $message = $this->createMessageMock();
        $topic = $this->createTopicMock();

        $producer = $this->createMessagePoducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->equalTo($topic), $this->equalTo($message))
        ;

        $factory = $this->createFactoryMock();
        $factory
            ->expects($this->once())
            ->method('createRouterMessage')
            ->with('messageName', 'messageBody')
            ->will($this->returnValue($message))
        ;
        $factory
            ->expects($this->once())
            ->method('createRouterTopic')
            ->will($this->returnValue($topic))
        ;
        $factory
            ->expects($this->once())
            ->method('createRouterMessageProducer')
            ->will($this->returnValue($producer))
        ;

        $zeroConfig = new ZeroConfig($factory);
        $zeroConfig->sendMessage('messageName', 'messageBody');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Topic
     */
    protected function createTopicMock()
    {
        return $this->getMock('Oro\Component\Messaging\Transport\Topic');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Message
     */
    protected function createMessageMock()
    {
        return $this->getMock('Oro\Component\Messaging\Transport\Message');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducer
     */
    protected function createMessagePoducerMock()
    {
        return $this->getMock('Oro\Component\Messaging\Transport\MessageProducer');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FactoryInterface
     */
    protected function createFactoryMock()
    {
        return $this->getMock('Oro\Component\Messaging\ZeroConfig\FactoryInterface');
    }
}
