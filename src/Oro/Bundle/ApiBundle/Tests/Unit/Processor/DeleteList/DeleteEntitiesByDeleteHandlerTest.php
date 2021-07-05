<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\DeleteList\DeleteEntitiesByDeleteHandler;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerInterface;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;
use Psr\Log\LoggerInterface;

class DeleteEntitiesByDeleteHandlerTest extends DeleteListProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityDeleteHandlerRegistry */
    private $deleteHandlerRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $logger;

    /** @var DeleteEntitiesByDeleteHandler */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->deleteHandlerRegistry = $this->createMock(EntityDeleteHandlerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new DeleteEntitiesByDeleteHandler(
            $this->doctrineHelper,
            $this->deleteHandlerRegistry,
            $this->logger
        );
    }

    public function testProcessWithoutResult()
    {
        $this->deleteHandlerRegistry->expects(self::never())
            ->method('getHandler');

        $this->logger->expects(self::never())
            ->method(self::anything());

        $this->processor->process($this->context);
    }

    public function testProcessForNotManageableEntity()
    {
        $entity = new \stdClass();
        $entityClass = \get_class($entity);
        $config = new EntityDefinitionConfig();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn(null);
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManagerForClass');
        $this->deleteHandlerRegistry->expects(self::never())
            ->method('getHandler');

        $this->logger->expects(self::never())
            ->method(self::anything());

        $this->context->setClassName($entityClass);
        $this->context->setResult([$entity]);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertEquals([$entity], $this->context->getResult());
    }

    public function testProcessForNotArrayResult()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'The result property of the context should be array or Traversable, "stdClass" given.'
        );

        $entity = new \stdClass();
        $entityClass = \get_class($entity);
        $config = new EntityDefinitionConfig();
        $deleteHandler = $this->createMock(EntityDeleteHandlerInterface::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn($entityClass);
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManagerForClass');

        $this->deleteHandlerRegistry->expects(self::once())
            ->method('getHandler')
            ->with($entityClass)
            ->willReturn($deleteHandler);
        $deleteHandler->expects(self::never())
            ->method('delete');

        $this->logger->expects(self::never())
            ->method(self::anything());

        $this->context->setClassName($entityClass);
        $this->context->setResult($entity);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $entity = new \stdClass();
        $entityClass = \get_class($entity);
        $config = new EntityDefinitionConfig();
        $deleteHandler = $this->createMock(EntityDeleteHandlerInterface::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn($entityClass);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with($entityClass)
            ->willReturn($em);
        $connection = $this->createMock(Connection::class);
        $em->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects(self::once())
            ->method('beginTransaction');
        $connection->expects(self::once())
            ->method('commit');

        $this->deleteHandlerRegistry->expects(self::once())
            ->method('getHandler')
            ->with($entityClass)
            ->willReturn($deleteHandler);
        $deleteHandler->expects(self::once())
            ->method('delete')
            ->with($entity, self::isFalse())
            ->willReturn(['entity' => $entity]);
        $deleteHandler->expects(self::once())
            ->method('flushAll')
            ->with([['entity' => $entity]]);

        $this->logger->expects(self::never())
            ->method(self::anything());

        $this->context->setClassName($entityClass);
        $this->context->setResult([$entity]);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessWithExceptionFromDeleteHandler()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('test exception');

        $entity = new \stdClass();
        $entityClass = \get_class($entity);
        $config = new EntityDefinitionConfig();
        $deleteHandler = $this->createMock(EntityDeleteHandlerInterface::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn($entityClass);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with($entityClass)
            ->willReturn($em);
        $connection = $this->createMock(Connection::class);
        $em->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects(self::once())
            ->method('beginTransaction');
        $connection->expects(self::never())
            ->method('commit');
        $connection->expects(self::once())
            ->method('rollBack');

        $exception = new \LogicException('test exception');
        $this->deleteHandlerRegistry->expects(self::once())
            ->method('getHandler')
            ->with($entityClass)
            ->willReturn($deleteHandler);
        $deleteHandler->expects(self::once())
            ->method('delete')
            ->with($entity)
            ->willThrowException($exception);
        $deleteHandler->expects(self::never())
            ->method('flushAll');

        $this->logger->expects(self::never())
            ->method(self::anything());

        $this->context->setClassName($entityClass);
        $this->context->setResult([$entity]);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }

    public function testProcessWithExceptionFromDeleteHandlerAndRollbackFailed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('test exception');

        $entity = new \stdClass();
        $entityClass = \get_class($entity);
        $config = new EntityDefinitionConfig();
        $deleteHandler = $this->createMock(EntityDeleteHandlerInterface::class);

        $rollbackException = new \LogicException('rollback exception');
        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn($entityClass);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with($entityClass)
            ->willReturn($em);
        $connection = $this->createMock(Connection::class);
        $em->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects(self::once())
            ->method('beginTransaction');
        $connection->expects(self::never())
            ->method('commit');
        $connection->expects(self::once())
            ->method('rollBack')
            ->willThrowException($rollbackException);

        $exception = new \LogicException('test exception');
        $this->deleteHandlerRegistry->expects(self::once())
            ->method('getHandler')
            ->with($entityClass)
            ->willReturn($deleteHandler);
        $deleteHandler->expects(self::once())
            ->method('delete')
            ->with($entity)
            ->willThrowException($exception);
        $deleteHandler->expects(self::never())
            ->method('flushAll');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'The database rollback operation failed in delete entities by delete handler API processor.',
                ['exception' => $rollbackException, 'entityClass' => $entityClass]
            );

        $this->context->setClassName($entityClass);
        $this->context->setResult([$entity]);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }

    public function testProcessForModelInheritedFromManageableEntity()
    {
        $entity = new \stdClass();
        $entityClass = \get_class($entity);
        $parentEntityClass = 'Test\Parent';
        $config = new EntityDefinitionConfig();
        $config->setParentResourceClass($parentEntityClass);
        $deleteHandler = $this->createMock(EntityDeleteHandlerInterface::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn($parentEntityClass);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with($parentEntityClass)
            ->willReturn($em);
        $connection = $this->createMock(Connection::class);
        $em->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects(self::once())
            ->method('beginTransaction');
        $connection->expects(self::once())
            ->method('commit');

        $this->deleteHandlerRegistry->expects(self::once())
            ->method('getHandler')
            ->with($parentEntityClass)
            ->willReturn($deleteHandler);
        $deleteHandler->expects(self::once())
            ->method('delete')
            ->with($entity, self::isFalse())
            ->willReturn(['entity' => $entity]);
        $deleteHandler->expects(self::once())
            ->method('flushAll')
            ->with([['entity' => $entity]]);

        $this->logger->expects(self::never())
            ->method(self::anything());

        $this->context->setClassName($entityClass);
        $this->context->setResult([$entity]);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }
}
