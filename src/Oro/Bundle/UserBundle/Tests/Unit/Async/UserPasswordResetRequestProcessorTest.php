<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Tests\Unit\Async;

use Oro\Bundle\UserBundle\Async\Topic\UserPasswordResetRequestTopic;
use Oro\Bundle\UserBundle\Async\UserPasswordResetRequestProcessor;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Event\PasswordChangeEvent;
use Oro\Bundle\UserBundle\Provider\UserLoggingInfoProviderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UserPasswordResetRequestProcessorTest extends TestCase
{
    private const TTL = 86400;

    private UserManager&MockObject $userManager;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private UserLoggingInfoProviderInterface&MockObject $userLoggingInfoProvider;
    private LoggerInterface&MockObject $logger;
    private UserPasswordResetRequestProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->userManager = $this->createMock(UserManager::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->userLoggingInfoProvider = $this->createMock(UserLoggingInfoProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new UserPasswordResetRequestProcessor(
            $this->userManager,
            $this->eventDispatcher,
            $this->userLoggingInfoProvider,
            $this->logger,
            self::TTL
        );
    }

    public function testSubscribedTopics(): void
    {
        self::assertEquals(
            [UserPasswordResetRequestTopic::getName()],
            UserPasswordResetRequestProcessor::getSubscribedTopics()
        );
    }

    public function testProcessWithNonExistentUser(): void
    {
        $this->userManager->expects(self::once())
            ->method('findUserByUsernameOrEmail')
            ->with('nonexistent_user')
            ->willReturn(null);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $this->userManager->expects(self::never())
            ->method('sendResetPasswordEmail');

        $this->userManager->expects(self::never())
            ->method('updateUser');

        self::assertEquals(MessageProcessorInterface::ACK, $this->process('nonexistent_user'));
    }

    public function testProcessWithDisabledUser(): void
    {
        $user = new User();
        $user->setEnabled(false);

        $this->userManager->expects(self::once())
            ->method('findUserByUsernameOrEmail')
            ->with('disabled_user')
            ->willReturn($user);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $this->userManager->expects(self::never())
            ->method('sendResetPasswordEmail');

        self::assertEquals(MessageProcessorInterface::ACK, $this->process('disabled_user'));
    }

    public function testProcessWhenEventDenies(): void
    {
        $user = $this->getUser();

        $this->userManager->expects(self::once())
            ->method('findUserByUsernameOrEmail')
            ->with('test_user')
            ->willReturn($user);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                self::isInstanceOf(PasswordChangeEvent::class),
                PasswordChangeEvent::BEFORE_PASSWORD_RESET
            )
            ->willReturnCallback(function (PasswordChangeEvent $event) {
                $event->disablePasswordChange('Test reason', 'Test log reason');

                return $event;
            });

        $this->userLoggingInfoProvider->expects(self::once())
            ->method('getUserLoggingInfo')
            ->with($user)
            ->willReturn([]);

        $this->logger->expects(self::once())
            ->method('notice')
            ->with('Password reset request denied (Test log reason).', []);

        $this->userManager->expects(self::never())
            ->method('sendResetPasswordEmail');

        $this->userManager->expects(self::never())
            ->method('updateUser');

        self::assertEquals(MessageProcessorInterface::ACK, $this->process('test_user'));
    }

    public function testProcessWhenPasswordRequestNotExpired(): void
    {
        $user = $this->getUser();
        $user->setPasswordRequestedAt(new \DateTime('now', new \DateTimeZone('UTC')));

        $this->userManager->expects(self::once())
            ->method('findUserByUsernameOrEmail')
            ->with('test_user')
            ->willReturn($user);

        $this->expectPasswordResetAllowed();

        $this->userLoggingInfoProvider->expects(self::once())
            ->method('getUserLoggingInfo')
            ->with($user)
            ->willReturn([]);

        $this->logger->expects(self::once())
            ->method('notice')
            ->with('The password for this user has already been requested within the last 24 hours.', []);

        $this->userManager->expects(self::never())
            ->method('sendResetPasswordEmail');

        $this->userManager->expects(self::never())
            ->method('updateUser');

        self::assertEquals(MessageProcessorInterface::ACK, $this->process('test_user'));
    }

    public function testProcessWhenPasswordRequestExpired(): void
    {
        $user = $this->getUser();
        $user->setPasswordRequestedAt(new \DateTime('-2 days', new \DateTimeZone('UTC')));

        $this->userManager->expects(self::once())
            ->method('findUserByUsernameOrEmail')
            ->with('test_user')
            ->willReturn($user);

        $this->expectPasswordResetAllowed();

        $this->userManager->expects(self::once())
            ->method('sendResetPasswordEmail')
            ->with($user);

        $this->userManager->expects(self::once())
            ->method('updateUser')
            ->with($user);

        $this->logger->expects(self::once())
            ->method('notice')
            ->with('Reset password email has been sent', []);

        self::assertEquals(MessageProcessorInterface::ACK, $this->process('test_user'));
    }

    public function testProcessSendsResetPasswordEmail(): void
    {
        $user = $this->getUser();

        $this->userManager->expects(self::once())
            ->method('findUserByUsernameOrEmail')
            ->with('test@example.com')
            ->willReturn($user);

        $this->expectPasswordResetAllowed();

        $this->userManager->expects(self::once())
            ->method('sendResetPasswordEmail')
            ->with($user);

        $this->userManager->expects(self::once())
            ->method('updateUser')
            ->with($user);

        $this->userLoggingInfoProvider->expects(self::once())
            ->method('getUserLoggingInfo')
            ->with($user)
            ->willReturn(['user' => ['id' => 1]]);

        $this->logger->expects(self::once())
            ->method('notice')
            ->with('Reset password email has been sent', ['user' => ['id' => 1]]);

        self::assertEquals(MessageProcessorInterface::ACK, $this->process('test@example.com'));
    }

    public function testProcessWithEmailSendingError(): void
    {
        $user = $this->getUser();
        $exception = new \Exception('Mailer is not available');

        $this->userManager->expects(self::once())
            ->method('findUserByUsernameOrEmail')
            ->with('test_user')
            ->willReturn($user);

        $this->expectPasswordResetAllowed();

        $this->userManager->expects(self::once())
            ->method('sendResetPasswordEmail')
            ->willThrowException($exception);

        $this->userManager->expects(self::never())
            ->method('updateUser');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unable to sent the reset password email.',
                ['email' => 'test@example.com', 'exception' => $exception]
            );

        self::assertEquals(MessageProcessorInterface::REJECT, $this->process('test_user'));
    }

    private function getUser(): User
    {
        $user = new User();
        $user->setEnabled(true);
        $user->setEmail('test@example.com');

        return $user;
    }

    private function expectPasswordResetAllowed(): void
    {
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                self::isInstanceOf(PasswordChangeEvent::class),
                PasswordChangeEvent::BEFORE_PASSWORD_RESET
            )
            ->willReturnArgument(0);
    }

    private function process(string $userIdentifier): string
    {
        $message = new Message();
        $message->setBody([UserPasswordResetRequestTopic::USER_IDENTIFIER => $userIdentifier]);

        return $this->processor->process($message, $this->createMock(SessionInterface::class));
    }
}
