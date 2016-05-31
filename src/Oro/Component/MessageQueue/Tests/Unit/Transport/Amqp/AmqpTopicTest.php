<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Amqp;

use Oro\Component\MessageQueue\Transport\Amqp\AmqpTopic;
use Oro\Component\MessageQueue\Transport\TopicInterface;
use Oro\Component\Testing\ClassExtensionTrait;

class AmqpTopicTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTopicInterface()
    {
        $this->assertClassImplements(TopicInterface::class, AmqpTopic::class);
    }

    public function testCouldBeConstructedWithNameAsArgument()
    {
        new AmqpTopic('aName');
    }

    public function testShouldAllowGetNameSetInConstructor()
    {
        $topic = new AmqpTopic('theName');

        $this->assertEquals('theName', $topic->getTopicName());
    }

    public function testShouldSetPassiveFalseInConstructor()
    {
        $topic = new AmqpTopic('aName');

        $this->assertFalse($topic->isPassive());
    }

    public function testShouldAllowGetPreviouslySetPassive()
    {
        $topic = new AmqpTopic('aName');
        $topic->setPassive(true);

        $this->assertTrue($topic->isPassive());
    }

    public function testShouldSetDurableFalseInConstructor()
    {
        $topic = new AmqpTopic('aName');

        $this->assertFalse($topic->isDurable());
    }

    public function testShouldAllowGetPreviouslySetDurable()
    {
        $topic = new AmqpTopic('aName');
        $topic->setDurable(true);

        $this->assertTrue($topic->isDurable());
    }

    public function testShouldSetNoWaitFalseInConstructor()
    {
        $topic = new AmqpTopic('aName');

        $this->assertFalse($topic->isNoWait());
    }

    public function testShouldAllowGetPreviouslySetNoWait()
    {
        $topic = new AmqpTopic('aName');
        $topic->setNoWait(true);

        $this->assertTrue($topic->isNoWait());
    }

    public function testShouldSetFanoutTypeInConstructor()
    {
        $topic = new AmqpTopic('aName');

        $this->assertEquals('fanout', $topic->getType());
    }

    public function testShouldAllowGetPreviouslySetType()
    {
        $topic = new AmqpTopic('aName');
        $topic->setType('theType');

        $this->assertEquals('theType', $topic->getType());
    }

    public function testShouldSetEmptyRoutingKeyInConstructor()
    {
        $topic = new AmqpTopic('aName');

        $this->assertSame('', $topic->getRoutingKey());
    }

    public function testShouldAllowGetPreviouslySetRoutingKey()
    {
        $topic = new AmqpTopic('aName');
        $topic->setRoutingKey('theRoutingKey');

        $this->assertEquals('theRoutingKey', $topic->getRoutingKey());
    }

    public function testShouldSetImmediateFalseInConstructor()
    {
        $topic = new AmqpTopic('aName');

        $this->assertFalse($topic->isImmediate());
    }

    public function testShouldAllowGetPreviouslySetImmediate()
    {
        $topic = new AmqpTopic('aName');
        $topic->setImmediate(true);

        $this->assertTrue($topic->isImmediate());
    }

    public function testShouldSetMandatoryFalseInConstructor()
    {
        $topic = new AmqpTopic('aName');

        $this->assertFalse($topic->isMandatory());
    }

    public function testShouldAllowGetPreviouslySetMandatory()
    {
        $topic = new AmqpTopic('aName');
        $topic->setMandatory(true);

        $this->assertTrue($topic->isMandatory());
    }

    public function testShouldSetEmptyArrayTableInConstructor()
    {
        $topic = new AmqpTopic('aName');

        $this->assertEquals([], $topic->getTable());
    }

    public function testShouldAllowGetPreviouslySetTable()
    {
        $topic = new AmqpTopic('aName');
        $topic->setTable(['aFoo' => 'aFooVal']);

        $this->assertEquals(['aFoo' => 'aFooVal'], $topic->getTable());
    }
}
