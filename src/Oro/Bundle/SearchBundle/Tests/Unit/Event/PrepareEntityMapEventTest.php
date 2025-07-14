<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Event;

use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product;
use PHPUnit\Framework\TestCase;

class PrepareEntityMapEventTest extends TestCase
{
    public function testEvent(): void
    {
        $entity = new Product();
        $class = get_class($entity);
        $data = [];
        $entityMapping = [
            'alias' => 'test'
        ];
        $event = new PrepareEntityMapEvent($entity, $class, $data, $entityMapping);
        $this->assertSame($entity, $event->getEntity());
        $this->assertSame($class, $event->getClassName());
        $this->assertSame($entityMapping, $event->getEntityMapping());
        $this->assertSame($data, $event->getData());
        $newData = [];
        $event->setData($newData);
        $this->assertSame($newData, $event->getData());
    }
}
