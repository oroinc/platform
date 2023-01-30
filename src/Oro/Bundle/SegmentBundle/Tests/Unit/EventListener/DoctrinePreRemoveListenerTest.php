<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentSnapshotRepository;
use Oro\Bundle\SegmentBundle\EventListener\DoctrinePreRemoveListener;
use Oro\Bundle\SegmentBundle\Tests\Unit\Fixtures\StubEntity;

class DoctrinePreRemoveListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var DoctrinePreRemoveListener */
    private $listener;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->listener = new DoctrinePreRemoveListener($this->configManager);
    }

    /**
     * @dataProvider preRemoveProvider
     */
    public function testPreRemove(bool $entityIsConfigurable = false)
    {
        $entity = new StubEntity();
        $args   = new LifecycleEventArgs($entity, $this->entityManager);

        $this->mockMetadata($entityIsConfigurable ? 1 : 0);
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->willReturn($entityIsConfigurable);

        $this->listener->preRemove($args);
    }

    public function preRemoveProvider(): array
    {
        return [
            'should process all configurable entities' => [true],
            'should not process all entities'          => [false]
        ];
    }

    /**
     * @dataProvider postFlushProvider
     */
    public function testPostFlushSegmentBundlePresent(array $entities)
    {
        $this->mockMetadata(count($entities));
        $this->configManager->expects($this->exactly(count($entities)))
            ->method('hasConfig')
            ->willReturn(true);

        foreach ($entities as $entity) {
            $args = new LifecycleEventArgs($entity['entity'], $this->entityManager);
            $this->listener->preRemove($args);
        }

        $configuration = new Configuration();
        $configuration->addEntityNamespace('OroSegmentBundle', 'OroSegmentBundleNamespace');
        $this->entityManager->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $repository = $this->createMock(SegmentSnapshotRepository::class);
        $repository->expects($this->once())
            ->method('massRemoveByEntities')
            ->with($entities);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $args = new PostFlushEventArgs($this->entityManager);
        $this->listener->postFlush($args);
    }

    /**
     * @dataProvider postFlushProvider
     */
    public function testPostFlushSegmentBundleNotPresent(array $entities)
    {
        $this->mockMetadata(count($entities));
        $this->configManager->expects($this->exactly(count($entities)))
            ->method('hasConfig')
            ->willReturn(true);

        foreach ($entities as $entity) {
            $args = new LifecycleEventArgs($entity['entity'], $this->entityManager);
            $this->listener->preRemove($args);
        }

        $configuration = new Configuration();
        $configuration->addEntityNamespace('SomeBundle', 'SomeBundleNamespace');
        $this->entityManager->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $this->entityManager->expects($this->never())
            ->method('getRepository');

        $args = new PostFlushEventArgs($this->entityManager);
        $this->listener->postFlush($args);
    }

    public function postFlushProvider(): array
    {
        return [
            'one entity' => [
                'entities' => $this->createEntities()
            ],
            'five entities' => [
                'entities' => $this->createEntities(5)
            ],
        ];
    }

    private function createEntities(int $count = 1): array
    {
        $entities = [];
        for ($i = 0; $i < $count; $i++) {
            $entity = new StubEntity();
            $entity->setId($i);
            $entity->setName('name-' . $i);
            $entities[] = [
                'id'     => $i,
                'entity' => $entity
            ];
        }
        return $entities;
    }

    private function mockMetadata(int $callCount): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->exactly($callCount))
            ->method('getIdentifierValues')
            ->willReturnCallback(function (StubEntity $currentEntity) {
                return [$currentEntity->getId()];
            });
        $this->entityManager->expects($this->exactly($callCount))
            ->method('getClassMetadata')
            ->willReturn($metadata);
    }
}
