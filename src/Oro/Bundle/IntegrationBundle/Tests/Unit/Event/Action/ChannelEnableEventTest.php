<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Event\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelEnableEvent;
use PHPUnit\Framework\TestCase;

class ChannelEnableEventTest extends TestCase
{
    public function testGetName(): void
    {
        $event = new ChannelEnableEvent(new Channel());

        self::assertSame('oro_integration.channel_enable', $event->getName());
    }

    public function testSettersGetters(): void
    {
        $channel = new Channel();
        $event = new ChannelEnableEvent($channel);

        $event->addError('error1');

        self::assertSame($channel, $event->getChannel());
        self::assertEquals(new ArrayCollection(['error1']), $event->getErrors());
    }
}
