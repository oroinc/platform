<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Mailer;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Sender\EmailTemplateSender;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Mailer\UserTemplateEmailSender;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserTemplateEmailSenderTest extends TestCase
{
    private const TEMPLATE_NAME = 'templateName';
    private const TEMPLATE_PARAMS = ['some' => 'params'];

    private NotificationSettings|MockObject $notificationSettingsModel;

    private EmailTemplateSender|MockObject $emailTemplateSender;

    private UserTemplateEmailSender $sender;

    protected function setUp(): void
    {
        $this->notificationSettingsModel = $this->createMock(NotificationSettings::class);
        $this->emailTemplateSender = $this->createMock(EmailTemplateSender::class);

        $this->sender = new UserTemplateEmailSender($this->notificationSettingsModel, $this->emailTemplateSender);
    }

    public function testSendUserTemplateEmailWithoutScope(): void
    {
        $user = new User();
        $sender = From::emailAddress('some@mail.com');

        $this->notificationSettingsModel->expects(self::atLeastOnce())
            ->method('getSender')
            ->willReturn($sender);

        $this->emailTemplateSender->expects(self::once())
            ->method('sendEmailTemplate')
            ->with(
                $sender,
                $user,
                self::TEMPLATE_NAME,
                self::TEMPLATE_PARAMS
            )
            ->willReturn($this->createMock(EmailUser::class));

        self::assertEquals(
            1,
            $this->sender->sendUserTemplateEmail($user, self::TEMPLATE_NAME, self::TEMPLATE_PARAMS)
        );
    }

    public function testSendUserTemplateEmailWithScope(): void
    {
        $user = new User();
        $sender = From::emailAddress('some@mail.com');
        $scopeEntity = new User();

        $this->notificationSettingsModel->expects(self::atLeastOnce())
            ->method('getSenderByScopeEntity')
            ->with($scopeEntity)
            ->willReturn($sender);

        $this->emailTemplateSender->expects(self::once())
            ->method('sendEmailTemplate')
            ->with(
                $sender,
                $user,
                self::TEMPLATE_NAME,
                self::TEMPLATE_PARAMS
            )
            ->willReturn($this->createMock(EmailUser::class));

        self::assertEquals(
            1,
            $this->sender->sendUserTemplateEmail($user, self::TEMPLATE_NAME, self::TEMPLATE_PARAMS, $scopeEntity)
        );
    }
}
