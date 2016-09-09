<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Event;

use Oro\Bundle\NotificationBundle\Event\NotificationSentEvent;
use Oro\Bundle\NotificationBundle\Entity\SpoolItem;

class NotificationSentEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $spoolItem = new SpoolItem();
        $event = new NotificationSentEvent($spoolItem, 1);
        $this->assertEquals($spoolItem, $event->getSpoolItem());
        $this->assertEquals(1, $event->getSentCount());
    }
}
