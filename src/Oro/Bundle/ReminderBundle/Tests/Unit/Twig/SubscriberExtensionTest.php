<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Twig;

use Oro\Bundle\ReminderBundle\Twig\SubscriberExtension;

class SubscriberExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SubscriberExtension
     */
    protected $target;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityContext;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $paramsProvider;

    public function setUp()
    {
        $this->securityContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->paramsProvider = $this->getMockBuilder('Oro\Bundle\ReminderBundle\Model\WebSocket\MessageParamsProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->target = new SubscriberExtension($this->entityManager, $this->securityContext, $this->paramsProvider);
    }

    public function testGetRequestedRemindersReturnAnEmptyArrayIfUserNotExist()
    {
        $token = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->getMockForAbstractClass();
        $token->expects($this->once())->method('getUser')->will($this->returnValue(null));
        $this->securityContext->expects($this->once())->method('getToken')->will($this->returnValue($token));

        $result = $this->target->getRequestedReminders();
        $actual = json_decode($result);
        $this->assertEquals(array(), $actual);
    }

    public function testGetRequestedRemindersReturnAnEmptyArrayIfUserNotEqualType()
    {
        $token = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->getMockForAbstractClass();
        $token->expects($this->once())->method('getUser')->will($this->returnValue(new \stdClass()));
        $this->securityContext->expects($this->once())->method('getToken')->will($this->returnValue($token));

        $result = $this->target->getRequestedReminders();
        $actual = json_decode($result);
        $this->assertEquals(array(), $actual);
    }

    public function testGetRequestedRemindersReturnCorrectData()
    {
        $reminder = $this->getMock('Oro\Bundle\ReminderBundle\Entity\Reminder');
        $reminder1 = $this->getMock('Oro\Bundle\ReminderBundle\Entity\Reminder');
        $reminder2 = $this->getMock('Oro\Bundle\ReminderBundle\Entity\Reminder');

        $expectedReminder = new \stdClass();
        $expectedReminder->id = 42;
        $expectedReminder1 = new \stdClass();
        $expectedReminder1->id = 12;
        $expectedReminder2 = new \stdClass();
        $expectedReminder2->id = 22;
        $expectedReminders = array($expectedReminder, $expectedReminder1, $expectedReminder2);

        $reminders = array($reminder, $reminder1, $reminder2);
        $token = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->getMockForAbstractClass();
        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')->disableOriginalConstructor()->getMock();
        $token->expects($this->once())->method('getUser')->will($this->returnValue($user));
        $repository = $this->getMockBuilder('Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findRequestedReminders')
            ->with($this->equalTo($user))
            ->will($this->returnValue($reminders));
        $this->entityManager->expects($this->once())
            ->method('getRepository')->will($this->returnValue($repository));
        $this->securityContext->expects($this->once())->method('getToken')->will($this->returnValue($token));

        $this->paramsProvider->expects($this->at(0))
            ->method('getMessageParams')
            ->with($this->identicalTo($reminder))
            ->will($this->returnValue($expectedReminder));

        $this->paramsProvider->expects($this->at(1))
            ->method('getMessageParams')
            ->with($this->identicalTo($reminder1))
            ->will($this->returnValue($expectedReminder1));

        $this->paramsProvider->expects($this->at(2))
            ->method('getMessageParams')
            ->with($this->identicalTo($reminder2))
            ->will($this->returnValue($expectedReminder2));

        $result = $this->target->getRequestedReminders();
        $actual = json_decode($result);
        $this->assertEquals($expectedReminders, $actual);
    }
}
