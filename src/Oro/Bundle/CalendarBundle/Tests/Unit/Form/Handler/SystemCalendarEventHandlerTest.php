<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Handler;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Handler\SystemCalendarEventHandler;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;

class SystemCalendarEventHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $om;

    /** @var SystemCalendarEventHandler */
    protected $handler;

    /** @var CalendarEvent */
    protected $entity;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $activityManager;

    protected function setUp()
    {
        $this->form                = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request             = new Request();
        $this->om                  = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->activityManager     = $this->getMockBuilder('Oro\Bundle\ActivityBundle\Manager\ActivityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity  = new CalendarEvent();
        $this->handler = new SystemCalendarEventHandler(
            $this->form,
            $this->request,
            $this->om,
            $this->activityManager
        );
    }

    public function testProcessWithContexts()
    {
        $context = new User();
        ReflectionUtil::setId($context, 123);

        $owner = new User();
        ReflectionUtil::setId($owner, 321);

        $this->request->setMethod('POST');
        $defaultCalendar = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entity->setCalendar($defaultCalendar);

        $this->form->expects($this->any())
            ->method('get')
            ->will($this->returnValue($this->form));

        $this->form->expects($this->once())
            ->method('has')
            ->with('contexts')
            ->will($this->returnValue(true));

        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $defaultCalendar->expects($this->once())
            ->method('getOwner')
            ->will($this->returnValue($owner));

        $this->form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue([$context]));

        $this->activityManager->expects($this->once())
            ->method('setActivityTargets')
            ->with(
                $this->identicalTo($this->entity),
                $this->identicalTo([$context, $owner])
            );

        $this->activityManager->expects($this->never())
            ->method('removeActivityTarget');
        $this->handler->process($this->entity);

        $this->assertSame($defaultCalendar, $this->entity->getCalendar());
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessInvalidData($method)
    {
        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->identicalTo($this->entity));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->identicalTo($this->request));
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));
        $this->om->expects($this->never())
            ->method('persist');
        $this->om->expects($this->never())
            ->method('flush');

        $this->assertFalse(
            $this->handler->process($this->entity)
        );
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessValidData($method)
    {
        $this->request->setMethod($method);

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
            ->method('persist');
        $this->om->expects($this->once())
            ->method('flush');

        $this->assertTrue(
            $this->handler->process($this->entity)
        );
    }

    public function supportedMethods()
    {
        return [
            ['POST'],
            ['PUT']
        ];
    }
}
