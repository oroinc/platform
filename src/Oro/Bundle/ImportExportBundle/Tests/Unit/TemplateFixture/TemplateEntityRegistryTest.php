<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\TemplateFixture;

use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRegistry;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRepositoryInterface;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;

class TemplateEntityRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var TemplateEntityRegistry */
    private $entityRegistry;

    protected function setUp(): void
    {
        $this->entityRegistry = new TemplateEntityRegistry();
    }

    public function testGettersAndSetters()
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();

        $this->entityRegistry->addEntity('Test\Entity', 'test1', $entity1);
        $this->entityRegistry->addEntity('Test\Entity', 'test2', $entity2);

        // entity1
        $this->assertTrue(
            $this->entityRegistry->hasEntity('Test\Entity', 'test1')
        );
        $this->assertSame(
            $entity1,
            $this->entityRegistry->getEntity('Test\Entity', 'test1')
        );

        // entity2
        $this->assertTrue(
            $this->entityRegistry->hasEntity('Test\Entity', 'test2')
        );
        $this->assertSame(
            $entity2,
            $this->entityRegistry->getEntity('Test\Entity', 'test2')
        );

        // unknown entity
        $this->assertFalse(
            $this->entityRegistry->hasEntity('Test\Entity', 'test3')
        );
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The entity "Test\Entity" with key "test3" does not exist.');
        $this->entityRegistry->getEntity('Test\Entity', 'test3');
    }

    public function testDuplicateAdd()
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();

        $this->entityRegistry->addEntity('Test\Entity', 'test1', $entity1);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The entity "Test\Entity" with key "test1" is already exist.');
        $this->entityRegistry->addEntity('Test\Entity', 'test1', $entity2);
    }

    public function testGetDataForSingleEntity()
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();

        $this->entityRegistry->addEntity('Test\Entity1', 'test1', $entity1);

        $repository1 = $this->createMock(TemplateEntityRepositoryInterface::class);
        $repository2 = $this->createMock(TemplateEntityRepositoryInterface::class);

        $templateManager = $this->createMock(TemplateManager::class);

        $repository1->expects($this->once())
            ->method('fillEntityData')
            ->with('test1', $this->identicalTo($entity1))
            ->willReturnCallback(function () use ($entity2) {
                $this->entityRegistry->addEntity('Test\Entity2', 'test2', $entity2);
            });
        $repository2->expects($this->once())
            ->method('fillEntityData')
            ->with('test2', $this->identicalTo($entity2));

        $templateManager->expects($this->exactly(2))
            ->method('getEntityRepository')
            ->willReturnMap([
                ['Test\Entity1', $repository1],
                ['Test\Entity2', $repository2]
            ]);

        $data = $this->entityRegistry->getData($templateManager, 'Test\Entity1', 'test1');
        $data = iterator_to_array($data);
        $this->assertCount(1, $data);

        $this->assertSame($entity1, current($data));
    }

    public function testGetDataForSeveralEntity()
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();
        $entity3 = new \stdClass();

        $this->entityRegistry->addEntity('Test\Entity1', 'test1', $entity1);

        $repository1 = $this->createMock(TemplateEntityRepositoryInterface::class);
        $repository2 = $this->createMock(TemplateEntityRepositoryInterface::class);

        $templateManager = $this->createMock(TemplateManager::class);

        $repository1->expects($this->exactly(2))
            ->method('fillEntityData')
            ->withConsecutive(['test1', $this->identicalTo($entity1)], ['test3', $this->identicalTo($entity3)])
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function () use ($entity2) {
                    $this->entityRegistry->addEntity('Test\Entity2', 'test2', $entity2);
                }),
                new ReturnCallback(function () {
                })
            );
        $repository2->expects($this->once())
            ->method('fillEntityData')
            ->with('test2', $this->identicalTo($entity2))
            ->willReturnCallback(function () use ($entity3) {
                $this->entityRegistry->addEntity('Test\Entity1', 'test3', $entity3);
            });

        $templateManager->expects($this->exactly(3))
            ->method('getEntityRepository')
            ->willReturnMap([
                ['Test\Entity1', $repository1],
                ['Test\Entity2', $repository2]
            ]);

        $data = $this->entityRegistry->getData($templateManager, 'Test\Entity1');
        $data = iterator_to_array($data);
        $this->assertCount(2, $data);

        $this->assertSame($entity1, $data[0]);
        $this->assertSame($entity3, $data[1]);
    }
}
