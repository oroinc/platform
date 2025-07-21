<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository;
use Oro\Bundle\ReminderBundle\Model\WebSocket\MessageParamsProvider;
use Oro\Bundle\ReminderBundle\Twig\ReminderExtension;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ReminderExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private TokenStorageInterface&MockObject $tokenStorage;
    private EntityManagerInterface&MockObject $entityManager;
    private MessageParamsProvider&MockObject $paramsProvider;
    private ReminderExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->paramsProvider = $this->createMock(MessageParamsProvider::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Reminder::class)
            ->willReturn($this->entityManager);

        $container = self::getContainerBuilder()
            ->add(TokenStorageInterface::class, $this->tokenStorage)
            ->add('oro_reminder.web_socket.message_params_provider', $this->paramsProvider)
            ->add(ManagerRegistry::class, $doctrine)
            ->getContainer($this);

        $this->extension = new ReminderExtension($container);
    }

    public function testGetRequestedRemindersReturnAnEmptyArrayIfUserNotExist(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(null);
        $this->tokenStorage->expects($this->atLeastOnce())
            ->method('getToken')
            ->willReturn($token);

        $this->assertEquals(
            [],
            self::callTwigFunction($this->extension, 'oro_reminder_get_requested_reminders_data', [])
        );
    }

    public function testGetRequestedRemindersReturnAnEmptyArrayIfUserNotEqualType(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class));
        $this->tokenStorage->expects($this->atLeastOnce())
            ->method('getToken')
            ->willReturn($token);

        $this->assertEquals(
            [],
            self::callTwigFunction($this->extension, 'oro_reminder_get_requested_reminders_data', [])
        );
    }

    public function testGetRequestedRemindersReturnCorrectData(): void
    {
        $reminder = $this->createMock(Reminder::class);
        $reminder1 = $this->createMock(Reminder::class);
        $reminder2 = $this->createMock(Reminder::class);

        $expectedReminder = new \stdClass();
        $expectedReminder->id = 42;
        $expectedReminder1 = new \stdClass();
        $expectedReminder1->id = 12;
        $expectedReminder2 = new \stdClass();
        $expectedReminder2->id = 22;
        $expectedReminders = [$expectedReminder, $expectedReminder1, $expectedReminder2];

        $reminders = [$reminder, $reminder1, $reminder2];
        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(User::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $repository = $this->createMock(ReminderRepository::class);
        $repository->expects($this->once())
            ->method('findRequestedReminders')
            ->with($user)
            ->willReturn($reminders);
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $this->tokenStorage->expects($this->atLeastOnce())
            ->method('getToken')
            ->willReturn($token);

        $this->paramsProvider->expects($this->once())
            ->method('getMessageParamsForReminders')
            ->with($reminders)
            ->willReturn($expectedReminders);

        $this->assertEquals(
            $expectedReminders,
            self::callTwigFunction($this->extension, 'oro_reminder_get_requested_reminders_data', [])
        );
    }
}
