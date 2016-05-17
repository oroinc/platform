<?php
namespace Oro\Component\Messaging\Tests\Transport\Null;

use Oro\Component\Messaging\Transport\Null\NullMessage;
use Oro\Component\Messaging\Transport\Null\NullMessageConsumer;
use Oro\Component\Messaging\Transport\Null\NullQueue;
use Oro\Component\Testing\ClassExtensionTrait;

class NullMessageConsumerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageConsumerInterface()
    {
        $this->assertClassImplements(
            'Oro\Component\Messaging\Transport\MessageConsumer',
            'Oro\Component\Messaging\Transport\Null\NullMessageConsumer'
        );
    }

    public function testCouldBeConstructedWithQueueAsArgument()
    {
        new NullMessageConsumer(new NullQueue('aName'));
    }

    public function testShouldAlwaysReturnNullOnReceive()
    {
        $consumer = new NullMessageConsumer(new NullQueue('theQueueName'));

        $this->assertNull($consumer->receive());
        $this->assertNull($consumer->receive());
        $this->assertNull($consumer->receive());
    }

    public function testShouldDoNothingOnAcknowledge()
    {
        $consumer = new NullMessageConsumer(new NullQueue('theQueueName'));

        $consumer->acknowledge(new NullMessage());
    }

    public function testShouldDoNothingOnReject()
    {
        $consumer = new NullMessageConsumer(new NullQueue('theQueueName'));

        $consumer->reject(new NullMessage());
    }
}
