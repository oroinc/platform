<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Handler;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Form\Handler\SystemCalendarHandler;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class SystemCalendarHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $om;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var SystemCalendarHandler */
    protected $handler;

    /** @var SystemCalendar */
    protected $entity;

    protected function setUp()
    {
        $this->form                = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request             = new Request();
        $this->om                  = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade      = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity  = new SystemCalendar();
        $this->handler = new SystemCalendarHandler(
            $this->form,
            $this->request,
            $this->om,
            $this->securityFacade
        );
    }

    /**
     * @dataProvider supportedMethods
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Current user did not define
     */
    public function testProcessGetRequestWithoutCurrentUser($method)
    {
        $this->request->setMethod($method);

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue(null));

        $this->handler->process($this->entity);
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessInvalidData($method)
    {
        $currentUser = new User();
        ReflectionUtil::setId($currentUser, 123);
        $organization = new Organization();
        ReflectionUtil::setId($organization, 1);

        $this->securityFacade->expects($this->exactly(1))
            ->method('getLoggedUser')
            ->will($this->returnValue($currentUser));
        $this->securityFacade->expects($this->exactly(2))
            ->method('getOrganization')
            ->will($this->returnValue($organization));

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
        $currentUser = new User();
        ReflectionUtil::setId($currentUser, 123);
        $organization = new Organization();
        ReflectionUtil::setId($organization, 1);

        $this->securityFacade->expects($this->exactly(1))
            ->method('getLoggedUser')
            ->will($this->returnValue($currentUser));
        $this->securityFacade->expects($this->exactly(2))
            ->method('getOrganization')
            ->will($this->returnValue($organization));

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
            [
                'POST'
            ]
        ];
    }
}
