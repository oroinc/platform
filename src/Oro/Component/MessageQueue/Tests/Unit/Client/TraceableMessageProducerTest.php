<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\CallbackMessageBuilder;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use Oro\Component\Testing\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class TraceableMessageProducerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageProducerInterface(): void
    {
        $this->assertClassImplements(MessageProducerInterface::class, TraceableMessageProducer::class);
    }

    public function testCouldBeConstructedWithInternalMessageProducer(): void
    {
        new TraceableMessageProducer($this->createMock(MessageProducerInterface::class));
    }

    public function testShouldPassAllArgumentsToInternalMessageProducerSendMethod(): void
    {
        $topic = 'theTopic';
        $body = 'theBody';

        $internalMessageProducer = $this->createMock(MessageProducerInterface::class);
        $internalMessageProducer->expects($this->once())
            ->method('send')
            ->with($topic, $body);

        $messageProducer = new TraceableMessageProducer($internalMessageProducer);

        $messageProducer->send($topic, $body);
    }

    public function testShouldAllowGetInfoSentToSameTopic(): void
    {
        $messageProducer = new TraceableMessageProducer($this->createMock(MessageProducerInterface::class));

        $messageProducer->send('aFooTopic', 'aFooBody');
        $messageProducer->send('aFooTopic', 'aFooBody');

        $this->assertEquals([
                ['topic' => 'aFooTopic', 'message' => 'aFooBody'],
                ['topic' => 'aFooTopic', 'message' => 'aFooBody'],
        ], $messageProducer->getTraces());
    }

    public function testShouldAllowGetInfoSentToDifferentTopics(): void
    {
        $messageProducer = new TraceableMessageProducer($this->createMock(MessageProducerInterface::class));

        $messageProducer->send('aFooTopic', 'aFooBody');
        $messageProducer->send('aBarTopic', 'aBarBody');

        $this->assertEquals([
            ['topic' => 'aFooTopic', 'message' => 'aFooBody'],
            ['topic' => 'aBarTopic', 'message' => 'aBarBody'],
        ], $messageProducer->getTraces());
    }

    public function testShouldResolveMessageIfItRepresentsByBuilder(): void
    {
        $messageProducer = new TraceableMessageProducer($this->createMock(MessageProducerInterface::class));

        $messageProducer->send('aFooTopic', new CallbackMessageBuilder(function () {
            return 'aFooBody';
        }));

        $this->assertEquals([
            ['topic' => 'aFooTopic', 'message' => 'aFooBody']
        ], $messageProducer->getTraces());
    }

    public function testShouldNotStoreAnythingIfInternalMessageProducerThrowsException(): void
    {
        $internalMessageProducer = $this->createMock(MessageProducerInterface::class);
        $internalMessageProducer->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception());

        $messageProducer = new TraceableMessageProducer($internalMessageProducer);

        $this->expectException(\Exception::class);
        try {
            $messageProducer->send('aFooTopic', 'aFooBody');
        } finally {
            $this->assertEmpty($messageProducer->getTraces());
        }
    }

    public function testShouldAllowClearStoredTraces(): void
    {
        $messageProducer = new TraceableMessageProducer($this->createMock(MessageProducerInterface::class));

        $messageProducer->send('aFooTopic', 'aFooBody');

        //guard
        $this->assertNotEmpty($messageProducer->getTraces());

        $messageProducer->clearTraces();
        $this->assertSame([], $messageProducer->getTraces());
    }
}
