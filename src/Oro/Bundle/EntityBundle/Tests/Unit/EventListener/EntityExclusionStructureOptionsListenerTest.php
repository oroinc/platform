<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\EventListener\EntityExclusionStructureOptionsListener;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Component\Testing\Unit\EntityTrait;

class EntityExclusionStructureOptionsListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var ExclusionProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $exclusionProvider;

    /** @var EntityExclusionStructureOptionsListener */
    protected $listener;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->exclusionProvider = $this->createMock(ExclusionProviderInterface::class);

        $this->listener = new EntityExclusionStructureOptionsListener($this->doctrineHelper, $this->exclusionProvider);
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

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory
            ->expects($this->once())
            ->method('getMetadataFor')
            ->with(Item::class)
            ->willReturn($metadata);

        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityManager')
            ->with(Item::class, false)
            ->willReturn($manager);

        $this->exclusionProvider
            ->expects($this->once())
            ->method('isIgnoredEntity')
            ->with(Item::class)
            ->willReturn(true);

        $this->exclusionProvider
            ->expects($this->once())
            ->method('isIgnoredField')
            ->with($metadata, 'field1')
            ->willReturn(true);

        $expectedEntityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => Item::class,
                'options' => [
                    EntityExclusionStructureOptionsListener::OPTION_NAME => true,
                ],
                'fields' => [
                    $this->getEntity(
                        EntityFieldStructure::class,
                        [
                            'name' => 'field1',
                            'options' => [
                                EntityExclusionStructureOptionsListener::OPTION_NAME => true,
                            ],
                        ]
                    )
                ]
            ]
        );

        $event = new EntityStructureOptionsEvent([$entityStructure]);

        $this->listener->onOptionsRequest($event);

        $this->assertEquals([$expectedEntityStructure], $event->getData());
    }

    public function testOnOptionsRequestUnsupported()
    {
        $this->doctrineHelper
            ->expects($this->never())
            ->method($this->anything());

        $this->exclusionProvider
            ->expects($this->never())
            ->method($this->anything());

        $this->exclusionProvider
            ->expects($this->never())
            ->method($this->anything());

        $event = new EntityStructureOptionsEvent([new \stdClass()]);

        $this->listener->onOptionsRequest($event);

        $this->assertEquals([new \stdClass()], $event->getData());
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

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityManager')
            ->with(Item::class, false)
            ->willReturn(null);

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
                    EntityExclusionStructureOptionsListener::OPTION_NAME => true,
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

        $event = new EntityStructureOptionsEvent([$entityStructure]);

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

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory
            ->expects($this->once())
            ->method('getMetadataFor')
            ->with(Item::class)
            ->willReturn(null);

        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityManager')
            ->with(Item::class, false)
            ->willReturn($manager);

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
                    EntityExclusionStructureOptionsListener::OPTION_NAME => true,
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

        $event = new EntityStructureOptionsEvent([$entityStructure]);

        $this->listener->onOptionsRequest($event);

        $this->assertEquals([$expectedEntityStructure], $event->getData());
    }
}
