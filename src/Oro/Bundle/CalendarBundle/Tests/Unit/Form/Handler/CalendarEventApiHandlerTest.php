<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Handler;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Handler\CalendarEventApiHandler;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;

class CalendarEventApiHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $form;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $om;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $emailSendProcessor;

    /** @var CalendarEvent */
    protected $obj;

    protected function setUp()
    {
        $this->form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = new Request();
        $this->om = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailSendProcessor = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Model\Email\EmailSendProcessor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->obj  = new CalendarEvent();

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->identicalTo($this->obj));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->identicalTo($this->request));
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));
        $this->om->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($this->obj));
        $this->om->expects($this->once())
            ->method('flush');
        $this->form->expects($this->once())
            ->method('get')
            ->will($this->returnValue($this->form));
        $this->form->expects($this->once())
            ->method('getData');
    }

    public function testProcessPOST()
    {
        $this->request->setMethod('POST');
        $this->emailSendProcessor->expects($this->once())
            ->method('sendInviteNotification');

        $handler = new CalendarEventApiHandler($this->form, $this->request, $this->om, $this->emailSendProcessor);
        $handler->process($this->obj);
    }

    public function testProcessPUT()
    {
        ReflectionUtil::setId($this->obj, 1);
        $this->request->setMethod('PUT');
        $this->emailSendProcessor->expects($this->once())
            ->method('sendUpdateParentEventNotification');

        $handler = new CalendarEventApiHandler($this->form, $this->request, $this->om, $this->emailSendProcessor);
        $handler->process($this->obj);
    }
}
