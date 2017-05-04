<?php

namespace Oro\Bundle\EntityConfigBundle\Test\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\BeforeEntityAttributeSaveEvent;

class BeforeEntityAttributeSaveEventTest extends \PHPUnit_Framework_TestCase
{

    public function testEvent()
    {
        /** @var EntityConfigModel|\PHPUnit_Framework_MockObject_MockObject $entityConfigModel */
        $entityConfigModel = $this->getMockBuilder(EntityConfigModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = new BeforeEntityAttributeSaveEvent(
            'product',
            $entityConfigModel,
            ['attribute' => ['is_attribute' => true]]
        );

        $this->assertEquals('product', $event->getAlias());
        $this->assertEquals(['attribute' => ['is_attribute' => true]], $event->getOptions());
        $this->assertSame($entityConfigModel, $event->getEntityConfigModel());
    }
}
