<?php
namespace Oro\Component\MessageQueue\Tests\Transport\Null;

use Oro\Component\MessageQueue\Transport\MessageProducer;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullMessageProducer;
use Oro\Component\MessageQueue\Transport\Null\NullTopic;
use Oro\Component\Testing\ClassExtensionTrait;

class NullMessageProducerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageProducerInterface()
    {
        $this->assertClassImplements(MessageProducer::class, NullMessageProducer::class);
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
