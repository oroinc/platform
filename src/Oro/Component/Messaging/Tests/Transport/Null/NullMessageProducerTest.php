<?php
namespace Oro\Component\Messaging\Tests\Transport\Null;

use Oro\Component\Messaging\Transport\Null\NullMessage;
use Oro\Component\Messaging\Transport\Null\NullMessageProducer;
use Oro\Component\Messaging\Transport\Null\NullTopic;
use Oro\Component\Testing\ClassExtensionTrait;

class NullMessageProducerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageProducerInterface()
    {
        $this->assertClassImplements(
            'Oro\Component\Messaging\Transport\MessageProducer',
            'Oro\Component\Messaging\Transport\Null\NullMessageProducer'
        );
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
