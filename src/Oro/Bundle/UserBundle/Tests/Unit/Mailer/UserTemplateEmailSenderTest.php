<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Mailer;

use Oro\Bundle\EmailBundle\Manager\EmailTemplateManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Mailer\UserTemplateEmailSender;

class UserTemplateEmailSenderTest extends \PHPUnit\Framework\TestCase
{
    private const TEMPLATE_NAME = 'templateName';
    private const TEMPLATE_PARAMS = ['some' => 'params'];

    /** @var NotificationSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $notificationSettingsModel;

    /** @var EmailTemplateManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailTemplateManager;

    /** @var UserTemplateEmailSender */
    private $sender;

    protected function setUp(): void
    {
        $this->notificationSettingsModel = $this->createMock(NotificationSettings::class);
        $this->emailTemplateManager = $this->createMock(EmailTemplateManager::class);

        $this->sender = new UserTemplateEmailSender($this->notificationSettingsModel, $this->emailTemplateManager);
    }

    public function testSendUserTemplateEmailWithoutScope(): void
    {
        $user = new User();
        $sender = From::emailAddress('some@mail.com');

        $this->notificationSettingsModel->expects($this->atLeastOnce())
            ->method('getSender')
            ->willReturn($sender);

        $returnValue = 1;
        $this->emailTemplateManager->expects($this->once())
            ->method('sendTemplateEmail')
            ->with(
                $sender,
                [$user],
                new EmailTemplateCriteria(self::TEMPLATE_NAME),
                self::TEMPLATE_PARAMS
            )
            ->willReturn($returnValue);

        self::assertEquals(
            $returnValue,
            $this->sender->sendUserTemplateEmail($user, self::TEMPLATE_NAME, self::TEMPLATE_PARAMS)
        );
    }

    public function testSendUserTemplateEmailWithtScope(): void
    {
        $user = new User();
        $sender = From::emailAddress('some@mail.com');
        $scopeEntity = new User();

        $this->notificationSettingsModel->expects($this->atLeastOnce())
            ->method('getSenderByScopeEntity')
            ->with($scopeEntity)
            ->willReturn($sender);

        $returnValue = 1;
        $this->emailTemplateManager->expects($this->once())
            ->method('sendTemplateEmail')
            ->with(
                $sender,
                [$user],
                new EmailTemplateCriteria(self::TEMPLATE_NAME),
                self::TEMPLATE_PARAMS
            )
            ->willReturn($returnValue);

        self::assertEquals(
            $returnValue,
            $this->sender->sendUserTemplateEmail($user, self::TEMPLATE_NAME, self::TEMPLATE_PARAMS, $scopeEntity)
        );
    }
}
