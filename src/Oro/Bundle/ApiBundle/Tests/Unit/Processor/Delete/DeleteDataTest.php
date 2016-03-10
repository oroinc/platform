<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Delete;

use Oro\Bundle\ApiBundle\Processor\Delete\DeleteData;

class DeleteDataTest extends DeleteContextTestCase
{
    /** @var DeleteData */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    public function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->processor = new DeleteData($this->doctrineHelper);
        parent::setUp();
    }

    public function testProcessWithoutObject()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManager');
        $this->processor->process($this->context);
    }

    public function testProcessOnNonObject()
    {
        $this->context->setObject('');
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManager');
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $object = new \stdClass();
        $this->context->setObject($object);

        $em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($object)
            ->willReturn($em);
        $em->expects($this->once())->method('remove')->with($object);
        $em->expects($this->once())->method('flush');

        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasObject());
    }
}
