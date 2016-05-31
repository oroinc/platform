<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Amqp;

use Oro\Component\MessageQueue\Transport\Amqp\AmqpQueue;
use Oro\Component\MessageQueue\Transport\QueueInterface;
use Oro\Component\Testing\ClassExtensionTrait;

class AmqpQueueTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementQueueInterface()
    {
        $this->assertClassImplements(QueueInterface::class, AmqpQueue::class);
    }

    public function testCouldBeConstructedWithNameAsArgument()
    {
        new AmqpQueue('aName');
    }

    public function testShouldAllowGetNameSetInConstructor()
    {
        $queue = new AmqpQueue('theName');

        $this->assertEquals('theName', $queue->getQueueName());
    }

    public function testShouldSetPassiveFalseInConstructor()
    {
        $queue = new AmqpQueue('aName');

        $this->assertFalse($queue->isPassive());
    }

    public function testShouldAllowGetPreviouslySetPassive()
    {
        $queue = new AmqpQueue('aName');
        $queue->setPassive(true);

        $this->assertTrue($queue->isPassive());
    }

    public function testShouldSetDurableTrueInConstructor()
    {
        $queue = new AmqpQueue('aName');

        $this->assertTrue($queue->isDurable());
    }

    public function testShouldAllowGetPreviouslySetDurable()
    {
        $queue = new AmqpQueue('aName');
        $queue->setDurable(false);

        $this->assertFalse($queue->isDurable());
    }

    public function testShouldSetExclusiveFalseInConstructor()
    {
        $queue = new AmqpQueue('aName');

        $this->assertFalse($queue->isExclusive());
    }

    public function testShouldAllowGetPreviouslySetExclusive()
    {
        $queue = new AmqpQueue('aName');
        $queue->setExclusive(true);

        $this->assertTrue($queue->isExclusive());
    }

    public function testShouldSetAutoDeleteFalseInConstructor()
    {
        $queue = new AmqpQueue('aName');

        $this->assertFalse($queue->isAutoDelete());
    }

    public function testShouldAllowGetPreviouslySetAutoDelete()
    {
        $queue = new AmqpQueue('aName');
        $queue->setAutoDelete(true);

        $this->assertTrue($queue->isAutoDelete());
    }

    public function testShouldSetNoWaitFalseInConstructor()
    {
        $queue = new AmqpQueue('aName');

        $this->assertFalse($queue->isNoWait());
    }

    public function testShouldAllowGetPreviouslySetNoWait()
    {
        $queue = new AmqpQueue('aName');
        $queue->setNoWait(true);

        $this->assertTrue($queue->isNoWait());
    }

    public function testShouldSetNoLocalFalseInConstructor()
    {
        $queue = new AmqpQueue('aName');

        $this->assertFalse($queue->isNoLocal());
    }

    public function testShouldAllowGetPreviouslySetNoLocal()
    {
        $queue = new AmqpQueue('aName');
        $queue->setNoLocal(true);

        $this->assertTrue($queue->isNoLocal());
    }

    public function testShouldSetNoAckFalseInConstructor()
    {
        $queue = new AmqpQueue('aName');

        $this->assertFalse($queue->isNoAck());
    }

    public function testShouldAllowGetPreviouslySetNoAck()
    {
        $queue = new AmqpQueue('aName');
        $queue->setNoAck(true);

        $this->assertTrue($queue->isNoAck());
    }

    public function testShouldSetEmptyConsumerTagInConstructor()
    {
        $queue = new AmqpQueue('aName');

        $this->assertEquals('', $queue->getConsumerTag());
    }

    public function testShouldAllowGetPreviouslySetConsumerTag()
    {
        $queue = new AmqpQueue('aName');
        $queue->setConsumerTag('theConsumerTag');

        $this->assertEquals('theConsumerTag', $queue->getConsumerTag());
    }

    public function testShouldSetEmptyArrayTableInConstructor()
    {
        $queue = new AmqpQueue('aName');

        $this->assertEquals([], $queue->getTable());
    }

    public function testShouldAllowGetPreviouslySetTable()
    {
        $queue = new AmqpQueue('aName');
        $queue->setTable(['aFoo' => 'aFooVal']);

        $this->assertEquals(['aFoo' => 'aFooVal'], $queue->getTable());
    }
}
