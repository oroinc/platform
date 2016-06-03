<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Handler;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Handler\CalendarEventApiHandler;
use Oro\Bundle\UserBundle\Entity\User;
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
    protected $entity;

    /** @var ActivityManager */
    protected $activityManager;

    /** @var CalendarEventHandler */
    protected $handler;

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
        $this->activityManager = $this->getMockBuilder('Oro\Bundle\ActivityBundle\Manager\ActivityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity  = new CalendarEvent();

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->identicalTo($this->entity));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->identicalTo($this->request));
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));
        $this->om->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($this->entity));
        $this->om->expects($this->once())
            ->method('flush');
        $this->form->expects($this->once())
            ->method('get')
            ->will($this->returnValue($this->form));
        $this->form->expects($this->once())
            ->method('getData');

        $this->handler = new CalendarEventApiHandler(
            $this->form,
            $this->request,
            $this->om,
            $this->emailSendProcessor,
            $this->activityManager
        );
    }

    public function testProcessPOST()
    {
        $this->request->setMethod('POST');
        $this->emailSendProcessor->expects($this->once())
            ->method('sendInviteNotification');

        $this->handler->process($this->entity);
    }

    public function testProcessWithContexts()
    {
        $context = new User();
        ReflectionUtil::setId($context, 123);

        $this->request->setMethod('POST');
        $defaultCalendar = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entity->setCalendar($defaultCalendar);

        $this->form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue([$context]));

        $this->activityManager->expects($this->never())
            ->method('removeActivityTarget');
        $this->handler->process($this->entity);


        $this->assertSame($defaultCalendar, $this->entity->getCalendar());
    }

    public function testProcessPUT()
    {
        ReflectionUtil::setId($this->entity, 1);
        $this->request->setMethod('PUT');
        $this->emailSendProcessor->expects($this->once())
            ->method('sendUpdateParentEventNotification');

        $this->handler->process($this->entity);
    }
}
