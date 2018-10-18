<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotification;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Handler\ResetPasswordHandler;
use Psr\Log\LoggerInterface;

class ResetPasswordHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EmailNotificationManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $emailNotificationManager;

    /**
     * @var UserManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $userManager;

    /**
     * @var Registry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var ResetPasswordHandler
     */
    private $handler;

    protected function setUp()
    {
        $this->emailNotificationManager = $this->createMock(EmailNotificationManager::class);
        $this->userManager = $this->createMock(UserManager::class);
        $this->registry = $this->createMock(Registry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = new ResetPasswordHandler(
            $this->emailNotificationManager,
            $this->userManager,
            $this->registry,
            $this->logger
        );
    }

    public function testResetPasswordAndNotifyIfUserDisabled()
    {
        $user = new User();
        $user->setEnabled(false);
        $this->userManager->expects($this->never())
            ->method('setAuthStatus');
        $this->userManager->expects($this->never())
            ->method('updateUser');
        $this->emailNotificationManager->expects($this->never())
            ->method('processSingle');

        $result = $this->handler->resetPasswordAndNotify($user);
        $this->assertFalse($result);
    }

    public function testResetPasswordAndNotifyIfThrowsException()
    {
        $email = 'example@test.com';
        $user = new User();
        $user->setEmail($email);
        $this->userManager->expects($this->once())
            ->method('setAuthStatus')
            ->with($user, UserManager::STATUS_EXPIRED);
        $this->userManager->expects($this->once())
            ->method('updateUser')
            ->with($user);
        $message = 'Message';
        $this->emailNotificationManager->expects($this->once())
            ->method('processSingle')
            ->willThrowException(new \Exception($message));
        $this->logger->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                [sprintf('Sending email to %s failed.', $email)],
                [$message]
            );

        $result = $this->handler->resetPasswordAndNotify($user);
        $this->assertFalse($result);
    }

    public function testResetPasswordAndNotifyWhenNoConfirmationToken()
    {
        $email = 'example@test.com';
        $user = new User();
        $user->setEmail($email);
        $this->userManager->expects($this->once())
            ->method('setAuthStatus')
            ->with($user, UserManager::STATUS_EXPIRED);
        $this->userManager->expects($this->once())
            ->method('updateUser')
            ->with($user);
        $this->emailNotificationManager->expects($this->once())
            ->method('processSingle')
            ->willReturnCallback(
                function (TemplateEmailNotification $notification, array $params, LoggerInterface $logger) use ($user) {
                    $this->assertSame($user, $notification->getEntity());
                    $this->assertInstanceOf(TemplateEmailNotification::class, $notification);
                    $this->assertEquals(
                        new EmailTemplateCriteria(ResetPasswordHandler::TEMPLATE_NAME, User::class),
                        $notification->getTemplateCriteria()
                    );
                    $this->assertEquals([$user], $notification->getRecipients());
                }
            );

        $this->assertEmpty($user->getConfirmationToken());
        $result = $this->handler->resetPasswordAndNotify($user);
        $this->assertTrue($result);
        $this->assertNotEmpty($user->getConfirmationToken());
    }

    public function testResetPasswordAndNotify()
    {
        $email = 'example@test.com';
        $token = 'sometoken';
        $user = new User();
        $user->setConfirmationToken($token);
        $user->setEmail($email);
        $this->userManager->expects($this->once())
            ->method('setAuthStatus')
            ->with($user, UserManager::STATUS_EXPIRED);
        $this->userManager->expects($this->once())
            ->method('updateUser')
            ->with($user);

        $expectedNotification = new TemplateEmailNotification(
            new EmailTemplateCriteria(ResetPasswordHandler::TEMPLATE_NAME, User::class),
            [$user],
            $user
        );
        $this->emailNotificationManager->expects($this->once())
            ->method('processSingle')
            ->with($expectedNotification, [], $this->logger);

        $result = $this->handler->resetPasswordAndNotify($user);
        $this->assertTrue($result);
        $this->assertEquals($token, $user->getConfirmationToken());
    }
}
