<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotification;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Handler\ResetPasswordHandler;
use Psr\Log\LoggerInterface;

class ResetPasswordHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailNotificationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $emailNotificationManager;

    /**
     * @var UserManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $userManager;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
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
            ->method('process');

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
        $this->getTemplate();
        $this->emailNotificationManager->expects($this->once())
            ->method('process')
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
        $template = $this->getTemplate();
        $this->emailNotificationManager->expects($this->once())
            ->method('process')
            ->willReturnCallback(
                function (User $passedUser, array $notifications, LoggerInterface $logger) use ($user, $template) {
                    $this->assertSame($user, $passedUser);
                    $this->assertCount(1, $notifications);
                    /** @var TemplateEmailNotification $notification */
                    $notification = reset($notifications);
                    $this->assertInstanceOf(TemplateEmailNotification::class, $notification);
                    $this->assertEquals($template, $notification->getTemplate());
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

        $expectedNotification = new TemplateEmailNotification($this->getTemplate(), [$user]);
        $this->emailNotificationManager->expects($this->once())
            ->method('process')
            ->with($user, [$expectedNotification], $this->logger);

        $result = $this->handler->resetPasswordAndNotify($user);
        $this->assertTrue($result);
        $this->assertEquals($token, $user->getConfirmationToken());
    }

    /**
     * @return EmailTemplate
     */
    private function getTemplate()
    {
        $template = new EmailTemplate();
        $repository = $this->createMock(EmailTemplateRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => ResetPasswordHandler::TEMPLATE_NAME, 'entityName' => User::class])
            ->willReturn($template);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(EmailTemplate::class)
            ->willReturn($repository);

        return $template;
    }
}
