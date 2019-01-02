<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\EventListener\EntityIdentifierStructureOptionsListener;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Component\Testing\Unit\EntityTrait;

class EntityIdentifierStructureOptionsListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $managerRegistry;

    /** @var EntityIdentifierStructureOptionsListener */
    protected $listener;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->listener = new EntityIdentifierStructureOptionsListener($this->managerRegistry);
    }

    public function testOnOptionsRequestIdentifier()
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
        $manager->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Item::class)
            ->willReturn($manager);

        $metadata->expects($this->once())
            ->method('isIdentifier')
            ->with('field1')
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
                                'identifier' => true
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

    public function testOnOptionsRequestWithoutObjectManager()
    {
        $entityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => Item::class,
                'fields' => [$this->getEntity(EntityFieldStructure::class, ['name' => 'field1'])]
            ]
        );

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Item::class)
            ->willReturn(null);

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
        $manager->expects($this->once())
            ->method('getClassMetadata')
            ->with(Item::class)
            ->willReturn(null);

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Item::class)
            ->willReturn($manager);

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
}
