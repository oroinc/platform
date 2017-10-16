<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\EventListener\EntityStructureOptionsListener;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityBundle\Provider\ChainVirtualFieldProvider;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;

class EntityStructureOptionsListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityAliasProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityAliasProvider;

    /** @var EntityStructureOptionsListener */
    protected $listener;

    /** @var EntityAliasProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $virtualFieldProvider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->entityAliasProvider = $this->createMock(EntityAliasProviderInterface::class);
        $this->virtualFieldProvider = $this->createMock(ChainVirtualFieldProvider::class);
        $this->listener = new EntityStructureOptionsListener($this->entityAliasProvider, $this->virtualFieldProvider);
    }

    public function testOnOptionsRequest()
    {
        $alias = 'ALIAS';
        $pluralAlias = 'PLURAL_ALIAS';
        $entity = $this->createMock(EntityStructure::class);
        $entity->expects($this->once())
            ->method('getClassName')
            ->willReturn(\stdClass::class);

        $entity->expects($this->once())
            ->method('setAlias')
            ->with($alias)
            ->willReturn($entity);

        $entity->expects($this->once())
            ->method('setPluralAlias')
            ->with($pluralAlias)
            ->willReturn($entity);

        $this->entityAliasProvider
            ->expects($this->once())
            ->method('getEntityAlias')
            ->with(\stdClass::class)
            ->willReturn(new EntityAlias($alias, $pluralAlias));

        $field = $this->createMock(EntityFieldStructure::class);

        $field->expects($this->once())
            ->method('getName')
            ->willReturn('field');

        $field->expects($this->once())
            ->method('addOption')
            ->with(EntityStructureOptionsListener::OPTION_NAME, true)
            ->willReturn($field);

        $entity->expects($this->once())
            ->method('getFields')
            ->willReturn([$field]);

        $this->virtualFieldProvider
            ->expects($this->once())
            ->method('isVirtualField')
            ->with(\stdClass::class, 'field')
            ->willReturn(true);

        $event = $this->createMock(EntityStructureOptionsEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn([$entity]);
        $event->expects($this->once())
            ->method('setData');

        $this->listener->onOptionsRequest($event);
    }
}
