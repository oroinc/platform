<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Twig;

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

    private MessageParamsProvider&MockObject $paramsProvider;
    private TokenStorageInterface&MockObject $tokenStorage;
    private ReminderRepository&MockObject $reminderRepository;
    private ReminderExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->paramsProvider = $this->createMock(MessageParamsProvider::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->reminderRepository = $this->createMock(ReminderRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(Reminder::class)
            ->willReturn($this->reminderRepository);

        $container = self::getContainerBuilder()
            ->add(MessageParamsProvider::class, $this->paramsProvider)
            ->add(TokenStorageInterface::class, $this->tokenStorage)
            ->add(ManagerRegistry::class, $doctrine)
            ->getContainer($this);

        $this->extension = new ReminderExtension($container);
    }

    public function testGetRequestedRemindersReturnAnEmptyArrayIfUserNotExist(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn(null);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertSame(
            [],
            self::callTwigFunction($this->extension, 'oro_reminder_get_requested_reminders_data', [])
        );
    }

    public function testGetRequestedRemindersReturnAnEmptyArrayIfUserNotEqualType(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class));
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertSame(
            [],
            self::callTwigFunction($this->extension, 'oro_reminder_get_requested_reminders_data', [])
        );
    }

    public function testGetRequestedRemindersReturnCorrectData(): void
    {
        $reminders = [$this->createMock(Reminder::class), $this->createMock(Reminder::class)];
        $expectedReminders = [['id' => 1], ['id' => 2]];

        $user = $this->createMock(User::class);

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->reminderRepository->expects(self::once())
            ->method('findRequestedReminders')
            ->with($user)
            ->willReturn($reminders);

        $this->paramsProvider->expects(self::once())
            ->method('getMessageParamsForReminders')
            ->with($reminders)
            ->willReturn($expectedReminders);

        self::assertSame(
            $expectedReminders,
            self::callTwigFunction($this->extension, 'oro_reminder_get_requested_reminders_data', [])
        );
    }
}
