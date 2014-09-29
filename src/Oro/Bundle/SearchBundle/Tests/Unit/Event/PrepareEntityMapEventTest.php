<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Event;


use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product;

class PrepareEntityMapEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $entity = new Product();
        $class = get_class($entity);
        $data = [];
        $event = new PrepareEntityMapEvent($entity, $class, $data);
        $this->assertSame($entity, $event->getEntity());
        $this->assertSame($class, $event->getClassName());
        $this->assertSame($data, $event->getData());
        $newData = [];
        $event->setData($newData);
        $this->assertSame($newData, $event->getData());
    }
}
