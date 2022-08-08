<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Event;

use Oro\Bundle\NotificationBundle\Event\NotificationEvent;

class NotificationEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var \stdClass */
    private $entity;

    /** @var NotificationEvent */
    private $event;

    protected function setUp(): void
    {
        $this->entity = new \stdClass();
        $this->event = new NotificationEvent($this->entity);
    }

    public function testGetEntity()
    {
        $this->assertEquals($this->entity, $this->event->getEntity());
        $this->event->setEntity(null);
        $this->assertNull($this->event->getEntity());
    }
}
