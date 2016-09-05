<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Handler;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Handler\CalendarEventHandler;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class CalendarEventHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $om;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $activityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityRoutingHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var CalendarEventHandler */
    protected $handler;

    /** @var CalendarEvent */
    protected $entity;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $emailSendProcessor;

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
        $this->entityRoutingHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade      = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailSendProcessor = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Model\Email\EmailSendProcessor')
            ->disableOriginalConstructor()
            ->getMock();

        $transformer = $this
            ->getMockBuilder('Oro\Bundle\CalendarBundle\Form\DataTransformer\UsersToAttendeesTransformer')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->entity  = new CalendarEvent();

        $this->handler = new CalendarEventHandler(
            $this->form,
            $this->request,
            $this->om,
            $this->activityManager,
            $this->entityRoutingHelper,
            $this->securityFacade,
            $this->emailSendProcessor,
            $transformer
        );
    }

    public function testProcessGetRequestWithCalendar()
    {
        $calendar = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entity->setCalendar($calendar);

        $this->form->expects($this->never())
            ->method('submit');

        $this->assertFalse(
            $this->handler->process($this->entity)
        );
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testProcessWithExceptionWithParent()
    {
        $this->entity->setParent(new CalendarEvent());
        $this->handler->process($this->entity);
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessInvalidData($method)
    {
        $calendar = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entity->setCalendar($calendar);

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
    public function testProcessValidDataWithoutTargetEntity($method)
    {
        $calendar = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entity->setCalendar($calendar);

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
        $this->form->expects($this->exactly(2))
            ->method('has')
            ->will($this->returnValue(false));
        $this->entityRoutingHelper->expects($this->once())
            ->method('getEntityClassName')
            ->will($this->returnValue(null));
        $this->om->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($this->entity));
        $this->om->expects($this->once())
            ->method('flush');

        $this->assertTrue(
            $this->handler->process($this->entity)
        );
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessWithContexts($method)
    {
        $context = new User();
        ReflectionUtil::setId($context, 123);

        $owner = new User();
        ReflectionUtil::setId($owner, 321);

        $organization = new Organization();
        ReflectionUtil::setId($organization, 1);
        $owner->setOrganization($organization);

        $this->request->setMethod($method);
        $this->form->expects($this->any())
            ->method('get')
            ->will($this->returnValue($this->form));

        $defaultCalendar = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entity->setCalendar($defaultCalendar);

        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->form->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive(
                ['contexts'],
                ['notifyInvitedUsers']
            )
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
        $this->assertTrue(
            $this->handler->process($this->entity)
        );

        $this->assertSame($defaultCalendar, $this->entity->getCalendar());
    }


    /**
     * @dataProvider supportedMethods
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Both logged in user and organization must be defined.
     */
    public function testProcessRequestWithoutCurrentUser($method)
    {
        $this->request->setMethod($method);

        $this->form->expects($this->never())
            ->method('submit')
            ->with($this->identicalTo($this->request));

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue(null));

        $this->handler->process($this->entity);
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessValidDataWithTargetEntityAssign($method)
    {
        $targetEntity = new User();
        ReflectionUtil::setId($targetEntity, 123);
        $organization = new Organization();
        ReflectionUtil::setId($organization, 1);
        $targetEntity->setOrganization($organization);
        $defaultCalendar = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entity->setCalendar($defaultCalendar);

        $this->entityRoutingHelper->expects($this->once())
            ->method('getEntityClassName')
            ->will($this->returnValue(get_class($targetEntity)));
        $this->entityRoutingHelper->expects($this->once())
            ->method('getEntityId')
            ->will($this->returnValue($targetEntity->getId()));
        $this->entityRoutingHelper->expects($this->once())
            ->method('getAction')
            ->will($this->returnValue('assign'));

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

        $this->entityRoutingHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(get_class($targetEntity), $targetEntity->getId())
            ->will($this->returnValue($targetEntity));

        $this->activityManager->expects($this->never())
            ->method('addActivityTarget')
            ->with($this->identicalTo($this->entity), $this->identicalTo($targetEntity));

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUserId')
            ->will($this->returnValue(100));

        $repository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('find', 'findAll', 'findBy', 'findOneBy', 'getClassName', 'findDefaultCalendar'))
            ->getMock();

        $calendar = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->disableOriginalConstructor()
            ->getMock();

        $repository ->expects($this->once())
            ->method('findDefaultCalendar')
            ->will($this->returnValue($calendar));

        $this->om->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $this->form->expects($this->exactly(2))
            ->method('has')
            ->will($this->returnValue(false));

        $this->om->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($this->entity));
        $this->om->expects($this->once())
            ->method('flush');

        $this->assertTrue(
            $this->handler->process($this->entity)
        );

        $this->assertNotSame($defaultCalendar, $this->entity->getCalendar());
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessValidDataWithTargetEntityActivity($method)
    {
        $targetEntity = new User();
        ReflectionUtil::setId($targetEntity, 123);
        $organization = new Organization();
        ReflectionUtil::setId($organization, 1);
        $targetEntity->setOrganization($organization);
        $defaultCalendar = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entity->setCalendar($defaultCalendar);

        $this->entityRoutingHelper->expects($this->once())
            ->method('getEntityClassName')
            ->will($this->returnValue(get_class($targetEntity)));
        $this->entityRoutingHelper->expects($this->once())
            ->method('getEntityId')
            ->will($this->returnValue($targetEntity->getId()));
        $this->entityRoutingHelper->expects($this->once())
            ->method('getAction')
            ->will($this->returnValue('activity'));

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

        $this->entityRoutingHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(get_class($targetEntity), $targetEntity->getId())
            ->will($this->returnValue($targetEntity));

        $this->activityManager->expects($this->once())
            ->method('addActivityTarget')
            ->with($this->identicalTo($this->entity), $this->identicalTo($targetEntity));
        $this->form->expects($this->exactly(2))
            ->method('has')
            ->will($this->returnValue(false));

        $this->om->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($this->entity));
        $this->om->expects($this->once())
            ->method('flush');

        $this->assertTrue(
            $this->handler->process($this->entity)
        );

        $this->assertSame($defaultCalendar, $this->entity->getCalendar());
    }

    public function supportedMethods()
    {
        return array(
            array('POST'),
            array('PUT')
        );
    }
}
