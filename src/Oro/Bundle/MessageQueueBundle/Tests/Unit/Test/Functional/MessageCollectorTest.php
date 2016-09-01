<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class MessageCollectorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $messageProducer;

    /** @var MessageCollector */
    private $messageCollector;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->messageProducer = $this->getMock(MessageProducerInterface::class);

        $this->messageCollector = new MessageCollector($this->messageProducer);
    }

    public function testShouldCallInternalMessageProducerSendMethod()
    {
        $topic = 'test topic';
        $message = 'test message';

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with($topic, $message);

        $this->messageCollector->send($topic, $message);
    }

    public function testShouldBeDisabledByDefault()
    {
        $this->messageCollector->send('test topic', 'test message');

        self::assertEquals([], $this->messageCollector->getSentMessages());
    }

    public function testShouldCollectMessagesWhenEnabled()
    {
        $topic = 'test topic';
        $message = 'test message';

        $this->messageCollector->enable();
        $this->messageCollector->send($topic, $message);

        self::assertEquals(
            [
                ['topic' => $topic, 'message' => $message]
            ],
            $this->messageCollector->getSentMessages()
        );
    }

    public function testShouldNotCollectMessagesWhenDisabled()
    {
        $this->messageCollector->enable();
        $this->messageCollector->disable();
        $this->messageCollector->send('test topic', 'test message');

        self::assertEquals([], $this->messageCollector->getSentMessages());
    }

    public function testShouldAllowClearCollectedMessages()
    {
        $this->messageCollector->enable();
        $this->messageCollector->send('test topic', 'test message');
        $this->messageCollector->clear();

        self::assertEquals([], $this->messageCollector->getSentMessages());
    }

    public function testShouldAllowClearCollectedMessagesEvenIfDisabled()
    {
        $this->messageCollector->enable();
        $this->messageCollector->send('test topic', 'test message');
        $this->messageCollector->disable();
        $this->messageCollector->clear();

        self::assertEquals([], $this->messageCollector->getSentMessages());
    }

    public function testShouldNotCatchExceptionFromInternalMessageProducer()
    {
        $exception = new \Exception('some error');

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->willThrowException($exception);

        $this->setExpectedException(get_class($exception), $exception->getMessage());

        $this->messageCollector->enable();
        $this->messageCollector->send('test topic', 'test message');
    }

    public function testShouldNotStoreMessageIfInternalMessageProducerThrowsException()
    {
        $this->messageProducer->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception());

        $this->messageCollector->enable();

        try {
            $this->messageCollector->send('test topic', 'test message');
        } catch (\Exception $e) {
            self::assertEquals([], $this->messageCollector->getSentMessages());
        }
    }
}
