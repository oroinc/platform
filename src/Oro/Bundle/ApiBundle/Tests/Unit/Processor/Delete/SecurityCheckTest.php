<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Delete;

use Oro\Bundle\ApiBundle\Processor\Delete\SecurityCheck;

class SecurityCheckTest extends DeleteContextTestCase
{
    /** @var SecurityCheck */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    public function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->processor = new SecurityCheck($this->securityFacade);
        parent::setUp();
    }

    public function testProcessWithoutObject()
    {
        $this->securityFacade->expects($this->never())
            ->method('isGranted');
        $this->processor->process($this->context);
    }

    public function testProcessWithoutNotAnObject()
    {
        $this->context->setObject('');
        $this->securityFacade->expects($this->never())
            ->method('isGranted');
        $this->processor->process($this->context);
    }

    public function testProcessWithGrantedObject()
    {
        $this->context->setObject(new \stdClass());
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @expectedExceptionMessage You have no access to delete given record
     */
    public function testProcessWithNotGrantedObject()
    {
        $this->context->setObject(new \stdClass());
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->willReturn(false);
        $this->processor->process($this->context);
    }
}