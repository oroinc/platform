<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\EventListener\IndexListener;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Manufacturer;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;

class IndexListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var IndexerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $searchIndexer;

    private array $entitiesMapping = [
        Product::class => [
            'fields' => [
                [
                    'name' => 'field',
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        $this->markTestSkipped('Due to BAP-13223');
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->searchIndexer = $this->createMock(IndexerInterface::class);
    }

    public function testOnFlush()
    {
        $insertedEntity = $this->createTestEntity('inserted');
        $updatedEntity = $this->createTestEntity('updated');
        $deletedEntity = $this->createTestEntity('deleted');
        $notSupportedEntity = new \stdClass();

        $entityClass = 'Product';
        $entityId = 1;
        $deletedEntityReference = new \stdClass();
        $deletedEntityReference->class = $entityClass;
        $deletedEntityReference->id = $entityId;

        $meta = $this->createClassMetadata();

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([
                'inserted' => $insertedEntity,
                'not_supported' => $notSupportedEntity,
            ]);
        $unitOfWork->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([
                'updated' => $updatedEntity,
                'not_supported' => $notSupportedEntity,
            ]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([
                'deleted' => $deletedEntity,
                'not_supported' => $notSupportedEntity,
            ]);
        $unitOfWork->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($updatedEntity)
            ->willReturn([
                'field' => ['val1', 'val2'],
            ]);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($deletedEntity)
            ->willReturn($entityClass);
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($deletedEntity)
            ->willReturn($entityId);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $entityManager->expects($this->once())
            ->method('getReference')
            ->with($entityClass, $entityId)
            ->willReturn($deletedEntityReference);
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($meta);

        $listener = $this->createListener();
        $listener->onFlush(new OnFlushEventArgs($entityManager));

        self::assertEquals(
            ['inserted' => $insertedEntity, 'updated' => $updatedEntity],
            ReflectionUtil::getPropertyValue($listener, 'savedEntities')
        );
        self::assertEquals(
            ['deleted' => $deletedEntityReference],
            ReflectionUtil::getPropertyValue($listener, 'deletedEntities')
        );
    }

    public function testPostFlushNoEntities()
    {
        $this->searchIndexer->expects($this->never())
            ->method('save');
        $this->searchIndexer->expects($this->never())
            ->method('delete');

        $listener = $this->createListener();
        $listener->postFlush(new PostFlushEventArgs($this->createMock(EntityManager::class)));
    }

    public function testPostFlush()
    {
        $insertedEntity = $this->createTestEntity('inserted');
        $insertedEntities = ['inserted' => $insertedEntity];
        $deletedEntity = $this->createTestEntity('deleted');
        $deletedEntities = ['deleted' => $deletedEntity];

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn($insertedEntities);
        $unitOfWork->expects($this->exactly(2))
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn($deletedEntities);

        $meta = $this->createClassMetadata();

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $entityManager->expects($this->once())
            ->method('getReference')
            ->willReturn($deletedEntity);
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($meta);

        $this->searchIndexer->expects($this->once())
            ->method('save')
            ->with($insertedEntities);

        $this->searchIndexer->expects($this->once())
            ->method('delete')
            ->with($deletedEntities);

        $listener = $this->createListener();
        $listener->onFlush(new OnFlushEventArgs($entityManager));
        $listener->postFlush(new PostFlushEventArgs($entityManager));

        self::assertFalse(ReflectionUtil::callMethod($listener, 'hasEntitiesToIndex', []));
    }

    public function testOnClear()
    {
        $insertedEntity = $this->createTestEntity('inserted');
        $insertedEntities = ['inserted' => $insertedEntity];
        $deletedEntity = $this->createTestEntity('deleted');
        $deletedEntities = ['deleted' => $deletedEntity];

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn($insertedEntities);
        $unitOfWork->expects($this->exactly(2))
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn($deletedEntities);

        $meta = $this->createClassMetadata();

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($meta);

        $listener = $this->createListener();
        $listener->onFlush(new OnFlushEventArgs($entityManager));
        $listener->onClear(new OnClearEventArgs($entityManager));

        self::assertFalse(ReflectionUtil::callMethod($listener, 'hasEntitiesToIndex', []));
    }

    private function createListener(): IndexListener
    {
        $listener = new IndexListener(
            $this->doctrineHelper,
            $this->searchIndexer,
            PropertyAccess::createPropertyAccessor()
        );

        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $mapperProvider = new SearchMappingProvider($eventDispatcher);
        $mapperProvider->setMappingConfig($this->entitiesMapping);
        $listener->setMappingProvider($mapperProvider);

        return $listener;
    }

    private function createTestEntity(string $name): Product
    {
        $result = new Product();
        $result->setName($name);
        $result->setManufacturer(new Manufacturer());

        return $result;
    }

    private function createClassMetadata(): ClassMetadata
    {
        $metaProperties = [
            [
                'inversedBy' => 'products',
                'targetEntity' => Product::class,
                'type' => ClassMetadataInfo::MANY_TO_ONE,
                'fieldName' => 'manufacturer',
            ]
        ];

        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects($this->any())
            ->method('getAssociationMappings')
            ->willReturnOnConsecutiveCalls($metaProperties, [], [], [], $metaProperties, [], [], []);

        return $meta;
    }
}
