<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Factory\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelDeleteEvent;
use Oro\Bundle\IntegrationBundle\Factory\Event\ChannelDeleteEventFactory;
use PHPUnit\Framework\TestCase;

class ChannelDeleteEventFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $channel = new Channel();
        $event = new ChannelDeleteEvent($channel);

        $factory = new ChannelDeleteEventFactory();

        self::assertEquals($event, $factory->create($channel));
    }
}
