<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\DeleteList\DeleteListDataByDeleteHandler;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;

class DeleteListDataByDeleteHandlerTest extends DeleteListProcessorTestCase
{
    /** @var DeleteListDataByDeleteHandler */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    protected function setUp()
    {
        parent::setUp();

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new DeleteListDataByDeleteHandler($this->doctrineHelper, $this->container);
    }

    public function testProcessWithoutResult()
    {
        $this->container->expects($this->never())->method('get');

        $this->processor->process($this->context);
    }

    public function testProcessOnNonObject()
    {
        $this->context->setResult('');
        $this->container->expects($this->never())->method('get');

        $this->processor->process($this->context);
    }

    public function testProcessForNotManageableEntity()
    {
        $entity = new \stdClass();
        $this->context->setClassName(get_class($entity));
        $this->context->setResult($entity);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(get_class($entity))
            ->willReturn(false);

        $this->container->expects($this->never())->method('get');

        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The result property of the Context should be array or Traversable, "stdClass" given.
     */
    public function testProcessForNotArrayResult()
    {
        $entity = new \stdClass();
        $this->context->setClassName(get_class($entity));
        $this->context->setResult($entity);
        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);

        $this->assertTrue($this->context->hasResult());

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(get_class($entity))
            ->willReturn(true);

        $deleteHandler = $this->getMockBuilder('Oro\Bundle\SoapBundle\Handler\DeleteHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->expects($this->once())
            ->method('get')
            ->with('oro_soap.handler.delete')
            ->willReturn($deleteHandler);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManagerForClass');

        $deleteHandler->expects($this->never())
            ->method('processDelete');

        $this->processor->process($this->context);
    }

    public function testProcessWithDefaultDeleteHandler()
    {
        $entity = new \stdClass();
        $this->context->setClassName(get_class($entity));
        $this->context->setResult([$entity]);
        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(get_class($entity))
            ->willReturn(true);

        $deleteHandler = $this->getMockBuilder('Oro\Bundle\SoapBundle\Handler\DeleteHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->expects($this->once())
            ->method('get')
            ->with('oro_soap.handler.delete')
            ->willReturn($deleteHandler);

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->once())
            ->method('beginTransaction');
        $connection->expects($this->once())
            ->method('commit');

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->exactly(2))
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(get_class($entity))
            ->willReturn($em);

        $deleteHandler->expects($this->once())
            ->method('processDelete')
            ->with($entity, $em);

        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasResult());
    }

    public function testProcessWithOwnDeleteHandler()
    {
        $user1 = new User();
        $user1->setId(1);
        $user1->setName('user1');

        $user2 = new User();
        $user2->setId(2);
        $user2->setName('user2');

        $this->context->setClassName(get_class($user1));
        $this->context->setResult([$user1, $user2]);

        $config = new EntityDefinitionConfig();
        $deleteHandlerServiceId = 'testDeleteHandler';
        $config->setDeleteHandler($deleteHandlerServiceId);
        $this->context->setConfig($config);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(get_class($user1))
            ->willReturn(true);

        $deleteHandler = $this->getMockBuilder('Oro\Bundle\SoapBundle\Handler\DeleteHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->expects($this->once())
            ->method('get')
            ->with($deleteHandlerServiceId)
            ->willReturn($deleteHandler);

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->once())
            ->method('beginTransaction');
        $connection->expects($this->once())
            ->method('commit');

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->exactly(2))
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(get_class($user1))
            ->willReturn($em);

        $deleteHandler->expects($this->exactly(2))
            ->method('processDelete');
        $deleteHandler->expects($this->at(0))
            ->method('processDelete')
            ->with($user1, $em);
        $deleteHandler->expects($this->at(1))
            ->method('processDelete')
            ->with($user2, $em);

        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasResult());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage test Exception
     */
    public function testProcessWithExceptionFromDeleteHandler()
    {
        $user1 = new User();
        $user1->setId(1);
        $user1->setName('user1');

        $this->context->setClassName(get_class($user1));
        $this->context->setResult([$user1]);

        $config = new EntityDefinitionConfig();
        $deleteHandlerServiceId = 'testDeleteHandler';
        $config->setDeleteHandler($deleteHandlerServiceId);
        $this->context->setConfig($config);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(get_class($user1))
            ->willReturn(true);

        $deleteHandler = $this->getMockBuilder('Oro\Bundle\SoapBundle\Handler\DeleteHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->expects($this->once())
            ->method('get')
            ->with($deleteHandlerServiceId)
            ->willReturn($deleteHandler);

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->once())
            ->method('beginTransaction');
        $connection->expects($this->never())
            ->method('commit');
        $connection->expects($this->once())
            ->method('rollBack');

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->exactly(2))
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(get_class($user1))
            ->willReturn($em);

        $exception = new \LogicException('test Exception');
        $deleteHandler->expects($this->once())
            ->method('processDelete')
            ->with($user1, $em)
            ->willThrowException($exception);

        $this->processor->process($this->context);
    }
}
