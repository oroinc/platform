<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Event\DefaultOwnerSetEvent;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;

class DefaultOwnerSetEventTest extends TestCase
{
    public function testInterface(): void
    {
        $channel = new Integration();
        $someOwner = $this->createMock(User::class);
        $channel->setDefaultUserOwner($someOwner);

        $event = new DefaultOwnerSetEvent($channel);

        $this->assertSame($channel, $event->getChannel());
        $this->assertSame($someOwner, $event->getDefaultUserOwner());
    }
}
