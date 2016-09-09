<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client\Meta;

use Oro\Component\MessageQueue\Client\Meta\DestinationMeta;

class DestinationMetaTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithExpectedArguments()
    {
        $destination = new DestinationMeta('aClientName', 'aTransportName');
        
        $this->assertAttributeEquals('aClientName', 'clientName', $destination);
        $this->assertAttributeEquals('aTransportName', 'transportName', $destination);
        $this->assertAttributeEquals([], 'subscribers', $destination);
    }

    public function testShouldAllowGetClientNameSetInConstructor()
    {
        $destination = new DestinationMeta('theClientName', 'aTransportName');
        
        $this->assertSame('theClientName', $destination->getClientName());
    }

    public function testShouldAllowGetTransportNameSetInConstructor()
    {
        $destination = new DestinationMeta('aClientName', 'theTransportName');

        $this->assertSame('theTransportName', $destination->getTransportName());
    }

    public function testShouldAllowGetSubscribersSetInConstructor()
    {
        $destination = new DestinationMeta('aClientName', 'aTransportName', ['aSubscriber']);

        $this->assertSame(['aSubscriber'], $destination->getSubscribers());
    }
}
