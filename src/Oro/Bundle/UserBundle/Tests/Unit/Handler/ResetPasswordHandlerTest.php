<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Handler;

use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotification;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Handler\ResetPasswordHandler;
use Psr\Log\LoggerInterface;

class ResetPasswordHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailNotificationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailNotificationManager;

    /** @var UserManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userManager;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ResetPasswordHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->emailNotificationManager = $this->createMock(EmailNotificationManager::class);
        $this->userManager = $this->createMock(UserManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new ResetPasswordHandler(
            $this->emailNotificationManager,
            $this->userManager,
            $this->logger
        );
    }

    public function testResetPasswordAndNotifyIfUserDisabled(): void
    {
        $user = new User();
        $user->setEnabled(false);
        $this->userManager->expects(self::never())
            ->method('setAuthStatus');
        $this->userManager->expects(self::never())
            ->method('updateUser');
        $this->emailNotificationManager->expects(self::never())
            ->method('processSingle');

        $result = $this->handler->resetPasswordAndNotify($user);
        self::assertFalse($result);
    }

    public function testResetPasswordAndNotifyIfThrowsException(): void
    {
        $email = 'example@test.com';
        $user = new User();
        $user->setEmail($email);
        $this->userManager->expects(self::once())
            ->method('setAuthStatus')
            ->with($user, UserManager::STATUS_RESET);
        $this->userManager->expects(self::once())
            ->method('updateUser')
            ->with($user);
        $message = 'Message';
        $this->emailNotificationManager->expects(self::once())
            ->method('processSingle')
            ->willThrowException(new \Exception($message));
        $this->logger->expects(self::exactly(2))
            ->method('error')
            ->withConsecutive(
                [sprintf('Sending email to %s failed.', $email)],
                [$message]
            );

        $result = $this->handler->resetPasswordAndNotify($user);
        self::assertFalse($result);
    }

    public function testResetPasswordAndNotifyWhenNoConfirmationToken(): void
    {
        $email = 'example@test.com';
        $user = new User();
        $user->setEmail($email);
        $this->userManager->expects(self::once())
            ->method('setAuthStatus')
            ->with($user, UserManager::STATUS_RESET);
        $this->userManager->expects(self::once())
            ->method('updateUser')
            ->with($user);
        $this->emailNotificationManager->expects(self::once())
            ->method('processSingle')
            ->willReturnCallback(
                function (TemplateEmailNotification $notification) use ($user) {
                    self::assertSame($user, $notification->getEntity());
                    self::assertInstanceOf(TemplateEmailNotification::class, $notification);
                    self::assertEquals(
                        new EmailTemplateCriteria('force_reset_password', User::class),
                        $notification->getTemplateCriteria()
                    );
                    self::assertEquals([$user], $notification->getRecipients());
                }
            );

        self::assertEmpty($user->getConfirmationToken());
        $result = $this->handler->resetPasswordAndNotify($user);
        self::assertTrue($result);
        self::assertNotEmpty($user->getConfirmationToken());
    }

    public function testResetPasswordAndNotify(): void
    {
        $email = 'example@test.com';
        $token = 'sometoken';
        $user = new User();
        $user->setConfirmationToken($token);
        $user->setEmail($email);
        $this->userManager->expects(self::once())
            ->method('setAuthStatus')
            ->with($user, UserManager::STATUS_RESET);
        $this->userManager->expects(self::once())
            ->method('updateUser')
            ->with($user);

        $expectedNotification = new TemplateEmailNotification(
            new EmailTemplateCriteria('force_reset_password', User::class),
            [$user],
            $user
        );
        $this->emailNotificationManager->expects(self::once())
            ->method('processSingle')
            ->with($expectedNotification, [], $this->logger);

        $result = $this->handler->resetPasswordAndNotify($user);
        self::assertTrue($result);
        self::assertEquals($token, $user->getConfirmationToken());
    }
}
