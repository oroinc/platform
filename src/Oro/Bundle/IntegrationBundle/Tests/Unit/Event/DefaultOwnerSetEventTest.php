<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Event\DefaultOwnerSetEvent;

class DefaultOwnerSetEventTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $channel   = new Integration();
        $someOwner = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $channel->setDefaultUserOwner($someOwner);

        $event = new DefaultOwnerSetEvent($channel);

        $this->assertSame($channel, $event->getChannel());
        $this->assertSame($someOwner, $event->getDefaultUserOwner());
    }
}
