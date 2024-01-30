<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Event;

use Oro\Bundle\SearchBundle\Event\BeforeIndexEntitiesEvent;
use Oro\Bundle\SearchBundle\Tests\Unit\Stub\EntityStub;

class BeforeIndexEntitiesEventTest extends \PHPUnit\Framework\TestCase
{
    public function testBeforeIndexEntitiesEvent(): void
    {
        $event = new BeforeIndexEntitiesEvent();
        self::assertEquals([], $event->getEntities());

        $entity = new EntityStub(1);
        $event->addEntity($entity);
        self::assertEquals([spl_object_id($entity) => $entity], $event->getEntities());

        $event->removeEntity($entity);
        self::assertEquals([], $event->getEntities());
    }
}
