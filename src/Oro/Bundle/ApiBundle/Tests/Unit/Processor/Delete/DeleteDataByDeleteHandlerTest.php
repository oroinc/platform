<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Delete;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Delete\DeleteContext;
use Oro\Bundle\ApiBundle\Processor\Delete\DeleteDataByDeleteHandler;

class DeleteDataByDeleteHandlerTest extends DeleteProcessorTestCase
{
    /** @var DeleteContext */
    protected $context;

    /** @var DeleteDataByDeleteHandler */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    public function setUp()
    {
        parent::setUp();

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new DeleteDataByDeleteHandler($this->doctrineHelper, $this->container);
    }

    public function testProcessWithoutObject()
    {
        $this->container->expects($this->never())
            ->method('get');

        $this->processor->process($this->context);
    }

    public function testProcessOnNonObject()
    {
        $this->context->setResult('');

        $this->container->expects($this->never())
            ->method('get');

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

        $this->container->expects($this->never())
            ->method('get');
        $this->processor->process($this->context);
    }

    public function testProcessWithDefaultDeleteHandler()
    {
        $entity = new \stdClass();
        $this->context->setClassName(get_class($entity));
        $this->context->setResult($entity);

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

        $em = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
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

    public function testProcessWithCustomDeleteHandler()
    {
        $entity = new \stdClass();
        $this->context->setClassName(get_class($entity));
        $this->context->setResult($entity);

        $config = new EntityDefinitionConfig();
        $deleteHandlerServiceId = 'testDeleteHandler';
        $config->setDeleteHandler($deleteHandlerServiceId);
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
            ->with($deleteHandlerServiceId)
            ->willReturn($deleteHandler);

        $em = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
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
}
