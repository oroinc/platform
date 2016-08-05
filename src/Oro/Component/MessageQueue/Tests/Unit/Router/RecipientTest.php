<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Router;

use Oro\Component\MessageQueue\Router\Recipient;
use Oro\Component\MessageQueue\Transport\DestinationInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;

class RecipientTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldAllowGetMessageSetInConstructor()
    {
        $message = $this->getMock(MessageInterface::class);

        $recipient = new Recipient($this->getMock(DestinationInterface::class), $message);

        $this->assertSame($message, $recipient->getMessage());
    }

    public function testShouldAllowGetDestinationSetInConstructor()
    {
        $destination = $this->getMock(DestinationInterface::class);

        $recipient = new Recipient($destination, $this->getMock(MessageInterface::class));

        $this->assertSame($destination, $recipient->getDestination());
    }
}
