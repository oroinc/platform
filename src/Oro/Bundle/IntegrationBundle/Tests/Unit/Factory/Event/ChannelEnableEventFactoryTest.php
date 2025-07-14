<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Factory\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelEnableEvent;
use Oro\Bundle\IntegrationBundle\Factory\Event\ChannelEnableEventFactory;
use PHPUnit\Framework\TestCase;

class ChannelEnableEventFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $channel = new Channel();
        $event = new ChannelEnableEvent($channel);

        $factory = new ChannelEnableEventFactory();

        self::assertEquals($event, $factory->create($channel));
    }
}
