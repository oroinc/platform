<?php
namespace Oro\Component\Messaging\Tests\Transport\Amqp;

use Oro\Component\Messaging\Transport\Amqp\AmqpQueue;
use Oro\Component\Testing\ClassExtensionTrait;

class AmqpQueueTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementQueueInterface()
    {
        $this->assertClassImplements(
            'Oro\Component\Messaging\Transport\Queue',
            'Oro\Component\Messaging\Transport\Amqp\AmqpQueue'
        );
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
        $topic = new AmqpQueue('aName');

        $this->assertFalse($topic->isPassive());
    }

    public function testShouldAllowGetPreviouslySetPassive()
    {
        $topic = new AmqpQueue('aName');
        $topic->setPassive(true);

        $this->assertTrue($topic->isPassive());
    }

    public function testShouldSetDurableTrueInConstructor()
    {
        $topic = new AmqpQueue('aName');

        $this->assertTrue($topic->isDurable());
    }

    public function testShouldAllowGetPreviouslySetDurable()
    {
        $topic = new AmqpQueue('aName');
        $topic->setDurable(false);

        $this->assertFalse($topic->isDurable());
    }

    public function testShouldSetExclusiveFalseInConstructor()
    {
        $topic = new AmqpQueue('aName');

        $this->assertFalse($topic->isExclusive());
    }

    public function testShouldAllowGetPreviouslySetExclusive()
    {
        $topic = new AmqpQueue('aName');
        $topic->setExclusive(true);

        $this->assertTrue($topic->isExclusive());
    }

    public function testShouldSetAutoDeleteFalseInConstructor()
    {
        $topic = new AmqpQueue('aName');

        $this->assertFalse($topic->isAutoDelete());
    }

    public function testShouldAllowGetPreviouslySetAutoDelete()
    {
        $topic = new AmqpQueue('aName');
        $topic->setAutoDelete(true);

        $this->assertTrue($topic->isAutoDelete());
    }

    public function testShouldSetNoWaitFalseInConstructor()
    {
        $topic = new AmqpQueue('aName');

        $this->assertFalse($topic->isNoWait());
    }

    public function testShouldAllowGetPreviouslySetNoWait()
    {
        $topic = new AmqpQueue('aName');
        $topic->setNoWait(true);

        $this->assertTrue($topic->isNoWait());
    }

    public function testShouldSetNoLocalFalseInConstructor()
    {
        $topic = new AmqpQueue('aName');

        $this->assertFalse($topic->isNoLocal());
    }

    public function testShouldAllowGetPreviouslySetNoLocal()
    {
        $topic = new AmqpQueue('aName');
        $topic->setNoLocal(true);

        $this->assertTrue($topic->isNoLocal());
    }

    public function testShouldSetNoAckFalseInConstructor()
    {
        $topic = new AmqpQueue('aName');

        $this->assertFalse($topic->isNoAck());
    }

    public function testShouldAllowGetPreviouslySetNoAck()
    {
        $topic = new AmqpQueue('aName');
        $topic->setNoAck(true);

        $this->assertTrue($topic->isNoAck());
    }

    public function testShouldSetEmptyConsumerTagInConstructor()
    {
        $topic = new AmqpQueue('aName');

        $this->assertEquals('', $topic->getConsumerTag());
    }

    public function testShouldAllowGetPreviouslySetConsumerTag()
    {
        $topic = new AmqpQueue('aName');
        $topic->setConsumerTag('theConsumerTag');

        $this->assertEquals('theConsumerTag', $topic->getConsumerTag());
    }
}
