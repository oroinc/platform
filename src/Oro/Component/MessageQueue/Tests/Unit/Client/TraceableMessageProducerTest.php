<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use Oro\Component\Testing\ClassExtensionTrait;

class TraceableMessageProducerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageProducerInterface()
    {
        $this->assertClassImplements(MessageProducerInterface::class, TraceableMessageProducer::class);
    }

    public function testCouldBeConstructedWithInternalMessageProducer()
    {
        new TraceableMessageProducer($this->createMessageProducer());
    }

    public function testShouldPassAllArgumentsToInternalMessageProducerSendMethod()
    {
        $topic = 'theTopic';
        $body = 'theBody';
        $priority = 'thePriority';

        $internalMessageProducer = $this->createMessageProducer();
        $internalMessageProducer
            ->expects($this->once())
            ->method('send')
            ->with($topic, $body, $priority)
        ;

        $messageProducer = new TraceableMessageProducer($internalMessageProducer);

        $messageProducer->send($topic, $body, $priority);
    }

    public function testShouldAllowGetInfoSentToSameTopic()
    {
        $messageProducer = new TraceableMessageProducer($this->createMessageProducer());

        $messageProducer->send('aFooTopic', 'aFooBody', 'aFooPriority');
        $messageProducer->send('aFooTopic', 'aFooBody', 'aFooPriority');

        $this->assertEquals([
            'aFooTopic' => [
                ['body' => 'aFooBody', 'priority' => 'aFooPriority'],
                ['body' => 'aFooBody', 'priority' => 'aFooPriority'],
            ]
        ], $messageProducer->getTraces());
    }

    public function testShouldAllowGetInfoSentToDifferentTopics()
    {
        $messageProducer = new TraceableMessageProducer($this->createMessageProducer());

        $messageProducer->send('aFooTopic', 'aFooBody', 'aFooPriority');
        $messageProducer->send('aBarTopic', 'aBarBody', 'aBarPriority');

        $this->assertEquals([
            'aFooTopic' => [
                ['body' => 'aFooBody', 'priority' => 'aFooPriority'],
            ],
            'aBarTopic' => [
                ['body' => 'aBarBody', 'priority' => 'aBarPriority'],
            ]
        ], $messageProducer->getTraces());
    }

    public function testShouldNotStoreAnythingIfInternalMessageProducerThrowsException()
    {
        $internalMessageProducer = $this->createMessageProducer();
        $internalMessageProducer
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception)
        ;

        $messageProducer = new TraceableMessageProducer($internalMessageProducer);

        $this->setExpectedException(\Exception::class);

        try {
            $messageProducer->send('aFooTopic', 'aFooBody', 'aFooPriority');
        } finally {
            $this->assertEmpty($messageProducer->getTraces());
        }
    }

    public function testShouldAllowClearStoredTraces()
    {
        $messageProducer = new TraceableMessageProducer($this->createMessageProducer());

        $messageProducer->send('aFooTopic', 'aFooBody', 'aFooPriority');


        //guard
        $this->assertNotEmpty($messageProducer->getTraces());

        $messageProducer->clearTraces();
        $this->assertSame([], $messageProducer->getTraces());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    protected function createMessageProducer()
    {
        return $this->getMock(MessageProducerInterface::class);
    }
}
