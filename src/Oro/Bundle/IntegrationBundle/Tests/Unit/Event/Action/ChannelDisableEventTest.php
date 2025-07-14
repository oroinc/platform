<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Event\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelDisableEvent;
use PHPUnit\Framework\TestCase;

class ChannelDisableEventTest extends TestCase
{
    public function testGetName(): void
    {
        $event = new ChannelDisableEvent(new Channel());

        self::assertSame('oro_integration.channel_disable', $event->getName());
    }

    public function testSettersGetters(): void
    {
        $channel = new Channel();
        $event = new ChannelDisableEvent($channel);

        $event->addError('error1');

        self::assertSame($channel, $event->getChannel());
        self::assertEquals(new ArrayCollection(['error1']), $event->getErrors());
    }
}
