<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityConfigBundle\EventListener\EntityConfigStructureOptionsListener;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class EntityConfigStructureOptionsListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    /** @var EntityConfigStructureOptionsListener */
    protected $listener;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->listener = new EntityConfigStructureOptionsListener($this->entityConfigProvider);
    }

    public function testOnOptionsRequest()
    {
        $entity = $this->createMock(EntityStructure::class);
        $entity->expects($this->once())
            ->method('getClassName')
            ->willReturn(\stdClass::class);

        $entity->expects($this->once())
            ->method('addOption')
            ->with(EntityConfigStructureOptionsListener::OPTION_NAME, true)
            ->willReturn($entity);

        $field = $this->createMock(EntityFieldStructure::class);

        $field->expects($this->once())
            ->method('getName')
            ->willReturn('field');

        $field->expects($this->once())
            ->method('addOption')
            ->with(EntityConfigStructureOptionsListener::OPTION_NAME, true)
            ->willReturn($field);

        $entity->expects($this->once())
            ->method('getFields')
            ->willReturn([$field]);

        $this->entityConfigProvider
            ->expects($this->exactly(2))
            ->method('hasConfig')
            ->withConsecutive(
                [\stdClass::class],
                [\stdClass::class, 'field']
            )
            ->willReturn(true);

        /** @var EntityStructureOptionsEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->createMock(EntityStructureOptionsEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn([$entity]);
        $event->expects($this->once())
            ->method('setData');

        $this->listener->onOptionsRequest($event);
    }
}
