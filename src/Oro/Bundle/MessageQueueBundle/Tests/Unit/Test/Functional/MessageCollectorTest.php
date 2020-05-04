<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Test\Functional;

use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\MessageQueueBundle\Client\MessageFilterInterface;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class MessageCollectorTest extends \PHPUnit\Framework\TestCase
{
    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $messageProducer;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $filter;

    /** @var MessageCollector */
    private $messageCollector;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->filter = $this->createMock(MessageFilterInterface::class);

        $this->messageCollector = new MessageCollector($this->messageProducer, $this->filter);
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

    public function testShouldCollectMessagesAndFilterThemBeforeGet()
    {
        $messages = [
            ['test topic 1', 'test message 1'],
            ['test topic 2', 'test message 2'],
        ];

        $this->filter->expects(self::once())
            ->method('apply')
            ->willReturnCallback(function (MessageBuffer $buffer) {
                $buffer->removeMessage(0);
            });

        $this->messageCollector->send($messages[0][0], $messages[0][1]);
        $this->messageCollector->send($messages[1][0], $messages[1][1]);

        self::assertSame(
            [['topic' => $messages[1][0], 'message' => $messages[1][1]]],
            $this->messageCollector->getSentMessages()
        );
        // test that filter is applied only once
        self::assertSame(
            [['topic' => $messages[1][0], 'message' => $messages[1][1]]],
            $this->messageCollector->getSentMessages()
        );
    }

    public function testShouldResetFilteredMessagesInSendMethod()
    {
        $messages = [
            ['test topic 1', 'test message 1'],
            ['test topic 2', 'test message 2'],
        ];

        $this->filter->expects(self::at(0))
            ->method('apply')
            ->willReturnCallback(function (MessageBuffer $buffer) {
                $buffer->removeMessage(0);
            });
        $this->filter->expects(self::at(1))
            ->method('apply')
            ->willReturnCallback(function (MessageBuffer $buffer) {
                $buffer->removeMessage(0);
                $buffer->removeMessage(2);
            });

        $this->messageCollector->send($messages[0][0], $messages[0][1]);
        $this->messageCollector->send($messages[1][0], $messages[1][1]);

        self::assertSame(
            [['topic' => $messages[1][0], 'message' => $messages[1][1]]],
            $this->messageCollector->getSentMessages()
        );
        // test that filtered messages are reset in send() method
        $this->messageCollector->send('topic3', 'test message 2');
        self::assertSame(
            [['topic' => $messages[1][0], 'message' => $messages[1][1]]],
            $this->messageCollector->getSentMessages()
        );
    }

    public function testShouldAllowClearCollectedMessages()
    {
        $this->filter->expects(self::never())
            ->method('apply');

        $this->messageCollector->send('test topic', 'test message');
        $this->messageCollector->clear();

        self::assertEquals([], $this->messageCollector->getSentMessages());
    }

    public function testShouldResetFilteredMessagesInClearMethod()
    {
        $this->filter->expects(self::once())
            ->method('apply');

        $this->messageCollector->send('test topic', 'test message');
        self::assertEquals(
            [['topic' => 'test topic', 'message' => 'test message']],
            $this->messageCollector->getSentMessages()
        );

        $this->messageCollector->clear();
        self::assertEquals([], $this->messageCollector->getSentMessages());
    }

    public function testShouldAllowClearCollectedTopicMessages()
    {
        $this->filter->expects(self::once())
            ->method('apply');

        $this->messageCollector->send('test topic 1', 'test message 1');
        $this->messageCollector->send('test topic 2', 'test message 2');
        $this->messageCollector->send('test topic 3', 'test message 3');
        $this->messageCollector->clearTopicMessages('test topic 1');

        self::assertEquals(
            [
                ['topic' => 'test topic 2', 'message' => 'test message 2'],
                ['topic' => 'test topic 3', 'message' => 'test message 3']
            ],
            $this->messageCollector->getSentMessages()
        );

        $this->messageCollector->clearTopicMessages('test topic 3');

        self::assertEquals(
            [['topic' => 'test topic 2', 'message' => 'test message 2']],
            $this->messageCollector->getSentMessages()
        );
    }
}
