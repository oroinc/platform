<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Factory\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelDisableEvent;
use Oro\Bundle\IntegrationBundle\Factory\Event\ChannelDisableEventFactory;

class ChannelDisableEventFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $channel = new Channel();
        $event = new ChannelDisableEvent($channel);

        $factory = new ChannelDisableEventFactory();

        static::assertEquals($event, $factory->create($channel));
    }
}
