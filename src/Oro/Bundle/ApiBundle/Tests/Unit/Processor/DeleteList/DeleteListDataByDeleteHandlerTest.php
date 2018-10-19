<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\DeleteList\DeleteListDataByDeleteHandler;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DeleteListDataByDeleteHandlerTest extends DeleteListProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var DeleteListDataByDeleteHandler */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new DeleteListDataByDeleteHandler($this->doctrineHelper, $this->container);
    }

    public function testProcessWithoutResult()
    {
        $this->container->expects(self::never())
            ->method('get');

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
        $this->container->expects(self::never())
            ->method('get');

        $this->context->setClassName($entityClass);
        $this->context->setResult([$entity]);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertEquals([$entity], $this->context->getResult());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The result property of the context should be array or Traversable, "stdClass" given.
     */
    public function testProcessForNotArrayResult()
    {
        $entity = new \stdClass();
        $entityClass = \get_class($entity);
        $config = new EntityDefinitionConfig();
        $deleteHandler = $this->createMock(DeleteHandler::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn($entityClass);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with($entityClass)
            ->willReturn($em);

        $this->container->expects(self::once())
            ->method('get')
            ->with('oro_soap.handler.delete')
            ->willReturn($deleteHandler);
        $deleteHandler->expects(self::never())
            ->method('processDelete');

        $this->context->setClassName($entityClass);
        $this->context->setResult($entity);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }

    public function testProcessWithDefaultDeleteHandler()
    {
        $entity = new \stdClass();
        $entityClass = \get_class($entity);
        $config = new EntityDefinitionConfig();
        $deleteHandler = $this->createMock(DeleteHandler::class);

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
        $em->expects(self::exactly(2))
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects(self::once())
            ->method('beginTransaction');
        $connection->expects(self::once())
            ->method('commit');

        $this->container->expects(self::once())
            ->method('get')
            ->with('oro_soap.handler.delete')
            ->willReturn($deleteHandler);
        $deleteHandler->expects(self::once())
            ->method('processDelete')
            ->with($entity, $em);

        $this->context->setClassName($entityClass);
        $this->context->setResult([$entity]);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessWithCustomDeleteHandler()
    {
        $entity = new \stdClass();
        $entityClass = \get_class($entity);
        $config = new EntityDefinitionConfig();
        $deleteHandlerServiceId = 'custom_delete_handler';
        $deleteHandler = $this->createMock(DeleteHandler::class);
        $config->setDeleteHandler($deleteHandlerServiceId);

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
        $em->expects(self::exactly(2))
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects(self::once())
            ->method('beginTransaction');
        $connection->expects(self::once())
            ->method('commit');

        $this->container->expects(self::once())
            ->method('get')
            ->with($deleteHandlerServiceId)
            ->willReturn($deleteHandler);
        $deleteHandler->expects(self::once())
            ->method('processDelete')
            ->with($entity, $em);

        $this->context->setClassName($entityClass);
        $this->context->setResult([$entity]);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage test exception
     */
    public function testProcessWithExceptionFromDeleteHandler()
    {
        $entity = new \stdClass();
        $entityClass = \get_class($entity);
        $config = new EntityDefinitionConfig();
        $deleteHandler = $this->createMock(DeleteHandler::class);

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
        $em->expects(self::exactly(2))
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects(self::once())
            ->method('beginTransaction');
        $connection->expects(self::never())
            ->method('commit');
        $connection->expects(self::once())
            ->method('rollBack');

        $exception = new \LogicException('test exception');
        $this->container->expects(self::once())
            ->method('get')
            ->with('oro_soap.handler.delete')
            ->willReturn($deleteHandler);
        $deleteHandler->expects(self::once())
            ->method('processDelete')
            ->with($entity, $em)
            ->willThrowException($exception);

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
        $deleteHandler = $this->createMock(DeleteHandler::class);

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
        $em->expects(self::exactly(2))
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects(self::once())
            ->method('beginTransaction');
        $connection->expects(self::once())
            ->method('commit');

        $this->container->expects(self::once())
            ->method('get')
            ->with('oro_soap.handler.delete')
            ->willReturn($deleteHandler);
        $deleteHandler->expects(self::once())
            ->method('processDelete')
            ->with($entity, $em);

        $this->context->setClassName($entityClass);
        $this->context->setResult([$entity]);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }
}
