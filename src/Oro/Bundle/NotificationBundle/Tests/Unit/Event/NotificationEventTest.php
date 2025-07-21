<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Event;

use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use PHPUnit\Framework\TestCase;

class NotificationEventTest extends TestCase
{
    private \stdClass $entity;
    private NotificationEvent $event;

    #[\Override]
    protected function setUp(): void
    {
        $this->entity = new \stdClass();
        $this->event = new NotificationEvent($this->entity);
    }

    public function testGetEntity(): void
    {
        $this->assertEquals($this->entity, $this->event->getEntity());
        $this->event->setEntity(null);
        $this->assertNull($this->event->getEntity());
    }
}
