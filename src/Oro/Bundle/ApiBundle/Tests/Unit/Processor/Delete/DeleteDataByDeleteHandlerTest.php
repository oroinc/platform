<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Delete;

use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Oro\Bundle\ApiBundle\Config\ActionsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Delete\DeleteContext;
use Oro\Bundle\ApiBundle\Processor\Delete\DeleteDataByDeleteHandler;

class DeleteDataByDeleteHandlerTest extends \PHPUnit_Framework_TestCase
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
        $configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = new DeleteContext($configProvider, $metadataProvider);

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

    public function testProcess()
    {
        $object = new \stdClass();
        $this->context->setResult($object);

        $deleteHandlerServiceName = 'testHandler';
        $actionsConfig = new ActionsConfig();
        $actionsConfig->set('delete', ['delete_handler' => $deleteHandlerServiceName]);
        $deleteHandler = $this->getMockBuilder('Oro\Bundle\SoapBundle\Handler\DeleteHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->setConfigExtras([new ActionsConfigExtra()]);
        $this->context->setConfigOf('actions', $actionsConfig);

        $this->container->expects($this->once())
            ->method('get')
            ->with($deleteHandlerServiceName)
            ->willReturn($deleteHandler);

        $em = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($object)
            ->willReturn($em);

        $deleteHandler->expects($this->once())
            ->method('processDelete')
            ->with($object, $em);

        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasResult());
    }
}
