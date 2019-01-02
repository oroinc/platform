<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\EventListener\EntityExclusionStructureOptionsListener;
use Oro\Bundle\EntityBundle\Helper\UnidirectionalFieldHelper;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Component\Testing\Unit\EntityTrait;

class EntityExclusionStructureOptionsListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $managerRegistry;

    /** @var ExclusionProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $exclusionProvider;

    /** @var EntityExclusionStructureOptionsListener */
    protected $listener;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->exclusionProvider = $this->createMock(ExclusionProviderInterface::class);

        $this->listener = new EntityExclusionStructureOptionsListener($this->managerRegistry, $this->exclusionProvider);
    }

    public function testOnOptionsRequestExcluded()
    {
        $entityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => Item::class,
                'fields' => [$this->getEntity(EntityFieldStructure::class, ['name' => 'field1'])]
            ]
        );

        $metadata = $this->createMock(ClassMetadata::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $this->managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(Item::class)
            ->willReturn($manager);

        $this->exclusionProvider
            ->expects($this->once())
            ->method('isIgnoredEntity')
            ->with(Item::class)
            ->willReturn(false);

        $this->exclusionProvider
            ->expects($this->once())
            ->method('isIgnoredField')
            ->with($metadata, 'field1')
            ->willReturn(true);

        $expectedEntityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => Item::class,
                'options' => [],
                'fields' => [
                    $this->getEntity(
                        EntityFieldStructure::class,
                        [
                            'name' => 'field1',
                            'options' => [
                                'exclude' => true
                            ]
                        ]
                    )
                ]
            ]
        );

        $event = new EntityStructureOptionsEvent();
        $event->setData([$entityStructure]);

        $this->listener->onOptionsRequest($event);

        $this->assertEquals([$expectedEntityStructure], $event->getData());
    }

    public function testOnOptionsRequestExcludedByRelatedClass()
    {
        $entityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => Item::class,
                'fields' => [
                    $this->getEntity(
                        EntityFieldStructure::class,
                        ['name' => 'field1', 'related_entity_name' => \stdClass::class]
                    )
                ]
            ]
        );

        $metadata = $this->createMock(ClassMetadata::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $this->managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(Item::class)
            ->willReturn($manager);

        $this->exclusionProvider
            ->expects($this->exactly(2))
            ->method('isIgnoredEntity')
            ->willReturnMap([
                [Item::class, false],
                [\stdClass::class, true],
            ]);

        $expectedEntityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => Item::class,
                'options' => [],
                'fields' => [
                    $this->getEntity(
                        EntityFieldStructure::class,
                        [
                            'name' => 'field1',
                            'options' => [
                                'exclude' => true
                            ],
                            'related_entity_name' => \stdClass::class
                        ]
                    )
                ]
            ]
        );

        $event = new EntityStructureOptionsEvent();
        $event->setData([$entityStructure]);

        $this->listener->onOptionsRequest($event);

        $this->assertEquals([$expectedEntityStructure], $event->getData());
    }

    public function testOnOptionsRequestClassExcluded()
    {
        $entityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => Item::class,
                'fields' => [$this->getEntity(EntityFieldStructure::class, ['name' => 'field1'])]
            ]
        );

        $this->exclusionProvider
            ->expects($this->once())
            ->method('isIgnoredEntity')
            ->with(Item::class)
            ->willReturn(true);

        $this->exclusionProvider
            ->expects($this->never())
            ->method('isIgnoredField');

        $expectedEntityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => Item::class,
                'options' => [
                    'exclude' => true
                ],
                'fields' => [
                    $this->getEntity(
                        EntityFieldStructure::class,
                        [
                            'name' => 'field1',
                        ]
                    )
                ]
            ]
        );

        $event = new EntityStructureOptionsEvent();
        $event->setData([$entityStructure]);

        $this->listener->onOptionsRequest($event);

        $this->assertEquals([$expectedEntityStructure], $event->getData());
    }

    public function testOnOptionsRequestWithoutObjectManager()
    {
        $entityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => Item::class,
                'fields' => [$this->getEntity(EntityFieldStructure::class, ['name' => 'field1'])]
            ]
        );

        $this->managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(Item::class)
            ->willReturn(null);

        $this->exclusionProvider
            ->expects($this->once())
            ->method('isIgnoredEntity')
            ->with(Item::class)
            ->willReturn(false);

        $this->exclusionProvider
            ->expects($this->never())
            ->method('isIgnoredField');

        $expectedEntityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => Item::class,
                'options' => [],
                'fields' => [
                    $this->getEntity(
                        EntityFieldStructure::class,
                        [
                            'name' => 'field1',
                        ]
                    )
                ]
            ]
        );

        $event = new EntityStructureOptionsEvent();
        $event->setData([$entityStructure]);

        $this->listener->onOptionsRequest($event);

        $this->assertEquals([$expectedEntityStructure], $event->getData());
    }

    public function testOnOptionsRequestWithoutMetadata()
    {
        $entityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => Item::class,
                'fields' => [$this->getEntity(EntityFieldStructure::class, ['name' => 'field1'])]
            ]
        );

        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn(null);

        $this->managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(Item::class)
            ->willReturn($manager);

        $this->exclusionProvider
            ->expects($this->once())
            ->method('isIgnoredEntity')
            ->with(Item::class)
            ->willReturn(false);

        $this->exclusionProvider
            ->expects($this->never())
            ->method('isIgnoredField');

        $expectedEntityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => Item::class,
                'options' => [],
                'fields' => [
                    $this->getEntity(
                        EntityFieldStructure::class,
                        [
                            'name' => 'field1',
                        ]
                    )
                ]
            ]
        );

        $event = new EntityStructureOptionsEvent();
        $event->setData([$entityStructure]);

        $this->listener->onOptionsRequest($event);

        $this->assertEquals([$expectedEntityStructure], $event->getData());
    }

    public function testOnOptionsRequestUnidirectional()
    {
        $fieldName = sprintf('class%sfield', UnidirectionalFieldHelper::DELIMITER);
        $entityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => Item::class,
                'fields' => [$this->getEntity(EntityFieldStructure::class, ['name' => $fieldName])]
            ]
        );

        $metadataEntity = $this->createMock(ClassMetadata::class);
        $metadataField = $this->createMock(ClassMetadata::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects($this->exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [Item::class, $metadataEntity],
                ['class', $metadataField],
            ]);

        $this->managerRegistry
            ->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [Item::class, $manager],
                ['class', $manager],
            ]);

        $this->exclusionProvider
            ->expects($this->once())
            ->method('isIgnoredEntity')
            ->with(Item::class)
            ->willReturn(false);

        $this->exclusionProvider
            ->expects($this->once())
            ->method('isIgnoredField')
            ->willReturn(true);

        $expectedEntityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => Item::class,
                'options' => [],
                'fields' => [
                    $this->getEntity(
                        EntityFieldStructure::class,
                        [
                            'name' => $fieldName,
                            'options' => [
                                'exclude' => true
                            ]
                        ]
                    )
                ]
            ]
        );

        $event = new EntityStructureOptionsEvent();
        $event->setData([$entityStructure]);

        $this->listener->onOptionsRequest($event);

        $this->assertEquals([$expectedEntityStructure], $event->getData());
    }
}
