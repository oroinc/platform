<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository;
use Oro\Bundle\ReminderBundle\Model\WebSocket\MessageParamsProvider;
use Oro\Bundle\ReminderBundle\Twig\ReminderExtension;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ReminderExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ReminderExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenStorage;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $paramsProvider;

    protected function setUp()
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paramsProvider = $this->getMockBuilder(MessageParamsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Reminder::class)
            ->willReturn($this->entityManager);

        $container = self::getContainerBuilder()
            ->add('security.token_storage', $this->tokenStorage)
            ->add('oro_reminder.web_socket.message_params_provider', $this->paramsProvider)
            ->add('doctrine', $doctrine)
            ->getContainer($this);

        $this->extension = new ReminderExtension($container);
    }

    public function testGetRequestedRemindersReturnAnEmptyArrayIfUserNotExist()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->will($this->returnValue(null));
        $this->tokenStorage->expects($this->atLeastOnce())->method('getToken')->will($this->returnValue($token));

        $this->assertEquals(
            [],
            self::callTwigFunction($this->extension, 'oro_reminder_get_requested_reminders_data', [])
        );
    }

    public function testGetRequestedRemindersReturnAnEmptyArrayIfUserNotEqualType()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->will($this->returnValue(new \stdClass()));
        $this->tokenStorage->expects($this->atLeastOnce())->method('getToken')->will($this->returnValue($token));

        $this->assertEquals(
            [],
            self::callTwigFunction($this->extension, 'oro_reminder_get_requested_reminders_data', [])
        );
    }

    public function testGetRequestedRemindersReturnCorrectData()
    {
        $reminder  = $this->createMock(Reminder::class);
        $reminder1 = $this->createMock(Reminder::class);
        $reminder2 = $this->createMock(Reminder::class);

        $expectedReminder      = new \stdClass();
        $expectedReminder->id  = 42;
        $expectedReminder1     = new \stdClass();
        $expectedReminder1->id = 12;
        $expectedReminder2     = new \stdClass();
        $expectedReminder2->id = 22;
        $expectedReminders     = array($expectedReminder, $expectedReminder1, $expectedReminder2);

        $reminders = array($reminder, $reminder1, $reminder2);
        $token     = $this->createMock(TokenInterface::class);
        $user      = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $token->expects($this->once())->method('getUser')->will($this->returnValue($user));
        $repository = $this->getMockBuilder(ReminderRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findRequestedReminders')
            ->with($this->equalTo($user))
            ->will($this->returnValue($reminders));
        $this->entityManager->expects($this->once())
            ->method('getRepository')->will($this->returnValue($repository));
        $this->tokenStorage->expects($this->atLeastOnce())->method('getToken')->will($this->returnValue($token));

        $this->paramsProvider->expects($this->once())
            ->method('getMessageParamsForReminders')
            ->with($reminders)
            ->will($this->returnValue($expectedReminders));

        $this->assertEquals(
            $expectedReminders,
            self::callTwigFunction($this->extension, 'oro_reminder_get_requested_reminders_data', [])
        );
    }
}
