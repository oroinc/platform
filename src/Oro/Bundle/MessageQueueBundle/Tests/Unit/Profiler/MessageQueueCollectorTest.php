<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Profiler;

use Oro\Bundle\MessageQueueBundle\Profiler\MessageQueueCollector;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class MessageQueueCollectorTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;
    
    public function testShouldExtendDataCollectorClass()
    {
        $this->assertClassExtends(DataCollector::class, MessageQueueCollector::class);
    }

    public function testCouldBeConstructedWithMessageProducerAsFirstArgument()
    {
        new MessageQueueCollector($this->createMessageProducerMock());
    }

    public function testShouldReturnExpectedName()
    {
        $collector = new MessageQueueCollector($this->createMessageProducerMock());

        $this->assertEquals('oro.message_queue', $collector->getName());
    }

    public function testShouldReturnEmptySentMessageArrayIfNotTraceableMessageProducer()
    {
        $collector = new MessageQueueCollector($this->createMessageProducerMock());

        $collector->collect(new Request(), new Response());

        $this->assertSame([], $collector->getSentMessages());
    }

    public function testShouldReturnSentMessageArrayTakenFromTraceableMessageProducer()
    {
        $producerMock = $this->createTraceableMessageProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('getTraces')
            ->willReturn([['foo'], ['bar']]);

        $collector = new MessageQueueCollector($producerMock);

        $collector->collect(new Request(), new Response());

        $this->assertSame([['foo'], ['bar']], $collector->getSentMessages());
    }

    public function testShouldPrettyPrintKnownPriority()
    {
        $collector = new MessageQueueCollector($this->createMessageProducerMock());

        $this->assertEquals('normal', $collector->prettyPrintPriority(MessagePriority::NORMAL));
    }

    public function testShouldPrettyPrintUnknownPriority()
    {
        $collector = new MessageQueueCollector($this->createMessageProducerMock());

        $this->assertEquals('unknownPriority', $collector->prettyPrintPriority('unknownPriority'));
    }

    public function testShouldPrettyPrintScalarMessage()
    {
        $collector = new MessageQueueCollector($this->createMessageProducerMock());

        $this->assertEquals('foo', $collector->prettyPrintMessage('foo'));
        $this->assertEquals('&lt;p&gt;', $collector->prettyPrintMessage('<p>'));
    }

    public function testShouldPrettyPrintArrayMessage()
    {
        $collector = new MessageQueueCollector($this->createMessageProducerMock());

        $expected = "[\n    &quot;foo&quot;,\n    &quot;bar&quot;\n]";

        $this->assertEquals($expected, $collector->prettyPrintMessage(['foo', 'bar']));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    protected function createMessageProducerMock()
    {
        return $this->getMock(MessageProducerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TraceableMessageProducer
     */
    protected function createTraceableMessageProducerMock()
    {
        return $this->getMock(TraceableMessageProducer::class, [], [], '', false);
    }
}