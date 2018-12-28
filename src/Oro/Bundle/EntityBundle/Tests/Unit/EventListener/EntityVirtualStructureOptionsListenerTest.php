<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\EventListener\EntityVirtualStructureOptionsListener;
use Oro\Bundle\EntityBundle\Helper\UnidirectionalFieldHelper;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityBundle\Provider\ChainVirtualFieldProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class EntityVirtualStructureOptionsListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var EntityVirtualStructureOptionsListener */
    protected $listener;

    /** @var ChainVirtualFieldProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $virtualFieldProvider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->virtualFieldProvider = $this->createMock(ChainVirtualFieldProvider::class);
        $this->listener = new EntityVirtualStructureOptionsListener($this->virtualFieldProvider);
    }

    public function testOnOptionsRequest()
    {
        $fieldStructure = (new EntityFieldStructure())->setName('field1');
        $entityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => \stdClass::class,
                'fields' => [$fieldStructure],
            ]
        );

        $this->virtualFieldProvider
            ->expects($this->once())
            ->method('isVirtualField')
            ->with($entityStructure->getClassName(), 'field1')
            ->willReturn(true);

        $event = $this->getEntity(EntityStructureOptionsEvent::class, ['data' => [$entityStructure]]);
        $expectedFieldStructure = (clone $fieldStructure)->addOption('virtual', true);
        $expectedEntityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => \stdClass::class,
                'fields' => [$expectedFieldStructure],
            ]
        );
        $this->listener->onOptionsRequest($event);
        $this->assertEquals([$expectedEntityStructure], $event->getData());
    }

    public function testOnOptionsRequestUnidirectional()
    {
        $fieldName = sprintf('class%sfield', UnidirectionalFieldHelper::DELIMITER);
        $fieldStructure = (new EntityFieldStructure())->setName($fieldName);
        $entityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => \stdClass::class,
                'fields' => [$fieldStructure],
            ]
        );

        $this->virtualFieldProvider
            ->expects($this->once())
            ->method('isVirtualField')
            ->with('class', 'field')
            ->willReturn(true);

        $event = $this->getEntity(EntityStructureOptionsEvent::class, ['data' => [$entityStructure]]);
        $expectedFieldStructure = (clone $fieldStructure)->addOption('virtual', true);
        $expectedEntityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => \stdClass::class,
                'fields' => [$expectedFieldStructure],
            ]
        );
        $this->listener->onOptionsRequest($event);
        $this->assertEquals([$expectedEntityStructure], $event->getData());
    }
}
