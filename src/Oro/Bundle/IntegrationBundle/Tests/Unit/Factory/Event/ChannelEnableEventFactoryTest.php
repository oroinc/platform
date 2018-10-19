<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Factory\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelEnableEvent;
use Oro\Bundle\IntegrationBundle\Factory\Event\ChannelEnableEventFactory;

class ChannelEnableEventFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $channel = new Channel();
        $event = new ChannelEnableEvent($channel);

        $factory = new ChannelEnableEventFactory();

        static::assertEquals($event, $factory->create($channel));
    }
}
