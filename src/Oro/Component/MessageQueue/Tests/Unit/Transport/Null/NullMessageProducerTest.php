<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Null;

use Oro\Component\MessageQueue\Transport\MessageProducerInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullMessageProducer;
use Oro\Component\MessageQueue\Transport\Null\NullTopic;
use Oro\Component\Testing\ClassExtensionTrait;

class NullMessageProducerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageProducerInterface()
    {
        $this->assertClassImplements(MessageProducerInterface::class, NullMessageProducer::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new NullMessageProducer();
    }

    public function testShouldDoNothingOnSend()
    {
        $producer = new NullMessageProducer();

        $producer->send(new NullTopic('aName'), new NullMessage());
    }
}
