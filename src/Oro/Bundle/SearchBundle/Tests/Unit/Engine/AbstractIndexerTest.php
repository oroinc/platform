<?php

declare(strict_types=1);

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\SearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Query\Mode;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/** @SuppressWarnings(PHPMD.TooManyPublicMethods) */
class AbstractIndexerTest extends TestCase
{
    private ObjectMapper|MockObject $mapper;
    private LoggerInterface|MockObject $logger;
    private AbstractIndexer|MockObject $indexer;

    #[\Override]
    protected function setUp(): void
    {
        $this->mapper = $this->createMock(ObjectMapper::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->indexer = $this->getMockForAbstractClass(
            AbstractIndexer::class,
            [
                $this->createMock(ManagerRegistry::class),
                $this->createMock(DoctrineHelper::class),
                $this->mapper,
                $this->createMock(EntityNameResolver::class),
                $this->logger
            ]
        );
    }

    public function testGetBatchSize(): void
    {
        $this->assertEquals(AbstractIndexer::BATCH_SIZE, $this->indexer->getBatchSize());
    }

    public function testSetLogger(): void
    {
        $newLogger = $this->createMock(LoggerInterface::class);
        $this->indexer->setLogger($newLogger);

        // Verify logger was set by triggering checkMappingErrors() which uses the logger
        $this->mapper->expects($this->once())
            ->method('getLastMappingErrors')
            ->willReturn(['TestEntity' => ['field1' => 'error message']]);

        $newLogger->expects($this->once())
            ->method('log');

        $reflection = new \ReflectionMethod($this->indexer, 'checkMappingErrors');
        $reflection->invoke($this->indexer);
    }

    public function testGetClassesForReindexWithoutClass(): void
    {
        $entities = ['Entity1', 'Entity2', 'Entity3'];

        $this->mapper->expects($this->once())
            ->method('getEntities')
            ->with([Mode::NORMAL, Mode::WITH_DESCENDANTS])
            ->willReturn($entities);

        $result = $this->indexer->getClassesForReindex();

        $this->assertEquals($entities, $result);
    }

    public function testGetClassesForReindexWithNormalMode(): void
    {
        $class = 'TestEntity';

        $this->mapper->expects($this->once())
            ->method('getEntityModeConfig')
            ->with($class)
            ->willReturn(Mode::NORMAL);

        $result = $this->indexer->getClassesForReindex($class);

        $this->assertEquals([$class], $result);
    }

    public function testGetClassesForReindexWithDescendantsMode(): void
    {
        $class = 'ParentEntity';
        $descendants = ['ChildEntity1', 'ChildEntity2'];

        $this->mapper->expects($this->once())
            ->method('getEntityModeConfig')
            ->with($class)
            ->willReturn(Mode::WITH_DESCENDANTS);

        $this->mapper->expects($this->once())
            ->method('getRegisteredDescendants')
            ->with($class)
            ->willReturn($descendants);

        $result = $this->indexer->getClassesForReindex($class);

        $this->assertEquals([$class, 'ChildEntity1', 'ChildEntity2'], $result);
    }

    public function testGetClassesForReindexWithOnlyDescendantsMode(): void
    {
        $class = 'ParentEntity';
        $descendants = ['ChildEntity1', 'ChildEntity2'];

        $this->mapper->expects($this->once())
            ->method('getEntityModeConfig')
            ->with($class)
            ->willReturn(Mode::ONLY_DESCENDANTS);

        $this->mapper->expects($this->once())
            ->method('getRegisteredDescendants')
            ->with($class)
            ->willReturn($descendants);

        $result = $this->indexer->getClassesForReindex($class);

        $this->assertEquals($descendants, $result);
    }

    public function testGetClassesForReindexWithDuplicates(): void
    {
        $class = 'ParentEntity';
        $descendants = ['ParentEntity', 'ChildEntity'];

        $this->mapper->expects($this->once())
            ->method('getEntityModeConfig')
            ->with($class)
            ->willReturn(Mode::WITH_DESCENDANTS);

        $this->mapper->expects($this->once())
            ->method('getRegisteredDescendants')
            ->with($class)
            ->willReturn($descendants);

        $result = $this->indexer->getClassesForReindex($class);

        $this->assertEquals(['ParentEntity', 'ChildEntity'], array_values($result));
    }

    public function testCheckMappingErrorsWithNoErrors(): void
    {
        $this->mapper->expects($this->once())
            ->method('getLastMappingErrors')
            ->willReturn([]);

        $this->logger->expects($this->never())->method('log');

        $reflection = new \ReflectionMethod($this->indexer, 'checkMappingErrors');
        $reflection->invoke($this->indexer);
    }

    public function testCheckMappingErrorsWithErrors(): void
    {
        $errors = [
            'test_entity' => ['field1' => 'error1', 'field2' => 'error2'],
            'another_entity' => ['field3' => 'error3']
        ];

        $this->mapper->expects($this->once())
            ->method('getLastMappingErrors')
            ->willReturn($errors);

        $this->logger->expects($this->exactly(2))->method('log');

        $reflection = new \ReflectionMethod($this->indexer, 'checkMappingErrors');
        $reflection->invoke($this->indexer);
    }

    public function testGetEntitiesArrayWithNull(): void
    {
        $reflection = new \ReflectionMethod($this->indexer, 'getEntitiesArray');
        $result = $reflection->invoke($this->indexer, null);

        $this->assertEquals([], $result);
    }

    public function testGetEntitiesArrayWithEmptyArray(): void
    {
        $reflection = new \ReflectionMethod($this->indexer, 'getEntitiesArray');
        $result = $reflection->invoke($this->indexer, []);

        $this->assertEquals([], $result);
    }

    public function testGetEntitiesArrayWithSingleEntity(): void
    {
        $entity = new \stdClass();

        $reflection = new \ReflectionMethod($this->indexer, 'getEntitiesArray');
        $result = $reflection->invoke($this->indexer, $entity);

        $this->assertEquals([$entity], $result);
    }

    public function testGetEntitiesArrayWithArrayOfEntities(): void
    {
        $entities = [new \stdClass(), new \stdClass()];

        $reflection = new \ReflectionMethod($this->indexer, 'getEntitiesArray');
        $result = $reflection->invoke($this->indexer, $entities);

        $this->assertSame($entities, $result);
    }

    public function testGetEntitiesArrayWithFalse(): void
    {
        $reflection = new \ReflectionMethod($this->indexer, 'getEntitiesArray');
        $result = $reflection->invoke($this->indexer, false);

        $this->assertEquals([], $result);
    }
}
