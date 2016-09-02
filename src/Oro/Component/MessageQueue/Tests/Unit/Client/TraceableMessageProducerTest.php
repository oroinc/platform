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

        $internalMessageProducer = $this->createMessageProducer();
        $internalMessageProducer
            ->expects($this->once())
            ->method('send')
            ->with($topic, $body)
        ;

        $messageProducer = new TraceableMessageProducer($internalMessageProducer);

        $messageProducer->send($topic, $body);
    }

    public function testShouldAllowGetInfoSentToSameTopic()
    {
        $messageProducer = new TraceableMessageProducer($this->createMessageProducer());

        $messageProducer->send('aFooTopic', 'aFooBody');
        $messageProducer->send('aFooTopic', 'aFooBody');

        $this->assertEquals([
                ['topic'=> 'aFooTopic', 'message' => 'aFooBody'],
                ['topic'=> 'aFooTopic', 'message' => 'aFooBody'],
        ], $messageProducer->getTraces());
    }

    public function testShouldAllowGetInfoSentToDifferentTopics()
    {
        $messageProducer = new TraceableMessageProducer($this->createMessageProducer());

        $messageProducer->send('aFooTopic', 'aFooBody');
        $messageProducer->send('aBarTopic', 'aBarBody');

        $this->assertEquals([
            ['topic'=> 'aFooTopic', 'message' => 'aFooBody'],
            ['topic' => 'aBarTopic', 'message' => 'aBarBody'],
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
            $messageProducer->send('aFooTopic', 'aFooBody');
        } finally {
            $this->assertEmpty($messageProducer->getTraces());
        }
    }

    public function testShouldAllowClearStoredTraces()
    {
        $messageProducer = new TraceableMessageProducer($this->createMessageProducer());

        $messageProducer->send('aFooTopic', 'aFooBody');


        //guard
        $this->assertNotEmpty($messageProducer->getTraces());

        $messageProducer->clear();
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
