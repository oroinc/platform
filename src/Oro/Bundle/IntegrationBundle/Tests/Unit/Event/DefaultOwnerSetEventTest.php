<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\DefaultOwnerSetEvent;

class DefaultOwnerSetEventTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $channel   = new Channel();
        $someOwner = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $channel->setDefaultUserOwner($someOwner);

        $event = new DefaultOwnerSetEvent($channel);

        $this->assertSame($channel, $event->getChannel());
        $this->assertSame($someOwner, $event->getDefaultUserOwner());
    }
}
