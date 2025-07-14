<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Factory\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelDisableEvent;
use Oro\Bundle\IntegrationBundle\Factory\Event\ChannelDisableEventFactory;
use PHPUnit\Framework\TestCase;

class ChannelDisableEventFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $channel = new Channel();
        $event = new ChannelDisableEvent($channel);

        $factory = new ChannelDisableEventFactory();

        self::assertEquals($event, $factory->create($channel));
    }
}
