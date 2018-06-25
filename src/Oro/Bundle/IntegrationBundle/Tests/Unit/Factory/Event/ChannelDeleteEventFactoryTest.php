<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Factory\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelDeleteEvent;
use Oro\Bundle\IntegrationBundle\Factory\Event\ChannelDeleteEventFactory;

class ChannelDeleteEventFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $channel = new Channel();
        $event = new ChannelDeleteEvent($channel);

        $factory = new ChannelDeleteEventFactory();

        static::assertEquals($event, $factory->create($channel));
    }
}
