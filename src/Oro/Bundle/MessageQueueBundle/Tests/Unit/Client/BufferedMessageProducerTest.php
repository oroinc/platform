<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Client;

use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class BufferedMessageProducerTest extends \PHPUnit\Framework\TestCase
{
    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $inner;

    /** @var BufferedMessageProducer */
    private $producer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->inner = $this->createMock(MessageProducerInterface::class);
        $this->producer = new BufferedMessageProducer($this->inner);
    }

    public function testBufferDisabledByDefault()
    {
        self::assertFalse($this->producer->isBufferingEnabled());
    }

    public function testEnableBuffering()
    {
        $this->producer->enableBuffering();
        self::assertTrue($this->producer->isBufferingEnabled());
    }

    public function testDisableBuffering()
    {
        $this->producer->enableBuffering();
        $this->producer->disableBuffering();
        self::assertFalse($this->producer->isBufferingEnabled());
    }

    public function testClearBuffer()
    {
        // send several messages to fill the buffer
        $messages = [
            ['test1', ['test_data']],
            ['test2', [new \stdClass()]]
        ];
        $this->producer->enableBuffering();
        foreach ($messages as list($topic, $message)) {
            $this->producer->send($topic, $message);
        }
        self::assertAttributeEquals($messages, 'buffer', $this->producer);

        // do the test
        $this->producer->clearBuffer();
        self::assertAttributeEquals([], 'buffer', $this->producer);
    }

    public function testFlushBuffer()
    {
        // send several messages to fill the buffer
        $messages = [
            ['test1', ['test_data']],
            ['test2', [new \stdClass()]]
        ];
        $this->producer->enableBuffering();
        foreach ($messages as list($topic, $message)) {
            $this->producer->send($topic, $message);
        }
        self::assertAttributeEquals($messages, 'buffer', $this->producer);

        // do the test
        $this->inner->expects(self::exactly(2))
            ->method('send');
        list($topic, $message) = $messages[0];
        $this->inner->expects(self::at(0))
            ->method('send')
            ->with($topic, $message);
        list($topic, $message) = $messages[1];
        $this->inner->expects(self::at(1))
            ->method('send')
            ->with($topic, $message);
        $this->producer->flushBuffer();
        self::assertAttributeEquals([], 'buffer', $this->producer);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The buffering of messages is disabled.
     */
    public function testDisableBufferingThrowExceptionOnFlush()
    {
        $this->producer->disableBuffering();
        $this->producer->flushBuffer();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The buffering of messages is disabled.
     */
    public function testDisableBufferingThrowExceptionOnClear()
    {
        $this->producer->disableBuffering();
        $this->producer->clearBuffer();
    }
}
