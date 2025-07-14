<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Data;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\EntityDataFactory;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EntityDataFactoryTest extends TestCase
{
    private string $entitiesClassName;
    /** @var MockObject[] */
    private array $entities = [];
    private MetadataRegistry&MockObject $metadataRegistry;
    private DoctrineHelper&MockObject $doctrineHelper;
    private EntityMetadata&MockObject $metadata;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private EntityDataFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->entitiesClassName = 'testClassNameForEntity';

        $this->entities[] = $this->getMockBuilder(\stdClass::class)
            ->setMockClassName($this->entitiesClassName)
            ->getMock();
        $this->entities[] = $this->getMockBuilder(\stdClass::class)
            ->setMockClassName($this->entitiesClassName)
            ->getMock();

        $this->metadataRegistry = $this->createMock(MetadataRegistry::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->metadata = $this->createMock(EntityMetadata::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->metadata->expects($this->any())
            ->method('getClassName')
            ->willReturn($this->entitiesClassName);
        $this->metadata->expects($this->any())
            ->method('getFieldsMetadata')
            ->willReturn([]);

        $this->metadataRegistry->expects($this->any())
            ->method('getEntityMetadata')
            ->with($this->entitiesClassName)
            ->willReturn($this->metadata);

        $this->factory = new EntityDataFactory(
            $this->metadataRegistry,
            $this->doctrineHelper,
            $this->eventDispatcher
        );
    }

    public function testCreateEntityData(): void
    {
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function ($event) {
                    self::assertInstanceOf(EntityDataEvent::class, $event);
                    self::assertInstanceOf(EntityData::class, $event->getEntityData());

                    return true;
                }),
                MergeEvents::CREATE_ENTITY_DATA
            );

        $result = $this->factory->createEntityData($this->entitiesClassName, $this->entities);
        $this->assertEquals($result->getClassName(), $this->entitiesClassName);
        $this->assertEquals($this->metadata, $result->getMetadata());
        $this->assertEquals($this->entities, $result->getEntities());
    }

    public function testCreateEntityDataByIds(): void
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntitiesByIds')
            ->with(
                $this->entitiesClassName,
                $this->callback(function ($params) {
                    return $params[0] == '12' && $params[1] == '88';
                })
            )
            ->willReturn($this->entities);

        $result = $this->factory->createEntityDataByIds($this->entitiesClassName, ['12', '88']);

        $this->assertEquals($this->entities, $result->getEntities());
    }
}
