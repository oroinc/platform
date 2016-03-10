<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Delete;

use Oro\Bundle\ApiBundle\Processor\Delete\DeleteDataByProcessHandler;

class DeleteDataByProcessHandlerTest extends DeleteContextTestCase
{
    /** @var DeleteDataByProcessHandler */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $deleteHandler;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    public function setUp()
    {
        $this->deleteHandler = $this->getMockBuilder('Oro\Bundle\SoapBundle\Handler\DeleteHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->buildProcessor();
        parent::setUp();
    }

    public function testProcessWithoutObject()
    {
        $this->deleteHandler->expects($this->never())
            ->method('processDelete');
        $this->processor->process($this->context);
    }

    public function testProcessOnNonObject()
    {
        $this->context->setObject('');
        $this->deleteHandler->expects($this->never())
            ->method('processDelete');
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $object = new \stdClass();
        $this->context->setObject($object);

        $em = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($object)
            ->willReturn($em);

        $this->deleteHandler->expects($this->once())
            ->method('processDelete')
            ->with($object, $em);

        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasObject());
    }

    protected function buildProcessor()
    {
        $this->processor = new DeleteDataByProcessHandler($this->doctrineHelper, $this->deleteHandler);
    }
}
