<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;

class NotificationSettingsTest extends \PHPUnit\Framework\TestCase
{
    private const SENDER_NAME = 'Sender';
    private const SENDER_EMAIL = 'some@mail.com';
    private const TEMPLATE_NAME = 'templateName';

    /**
     * @var ConfigManager|MockObject
     */
    private $configManager;

    /**
     * @var NotificationSettings
     */
    private $model;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->model = new NotificationSettings($this->configManager);
    }

    public function testGetSender(): void
    {
        $this->configManager
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['oro_notification.email_notification_sender_email', false, false, null, self::SENDER_EMAIL],
                ['oro_notification.email_notification_sender_name', false, false, null, self::SENDER_NAME]
            ]);

        self::assertEquals(From::emailAddress(self::SENDER_EMAIL, self::SENDER_NAME), $this->model->getSender());
    }

    public function testGetSenderByEntityScope(): void
    {
        $entity = new User();
        $this->configManager
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['oro_notification.email_notification_sender_email', false, false, $entity, self::SENDER_EMAIL],
                ['oro_notification.email_notification_sender_name', false, false, $entity, self::SENDER_NAME]
            ]);

        self::assertEquals(
            From::emailAddress(self::SENDER_EMAIL, self::SENDER_NAME),
            $this->model->getSenderByScopeEntity($entity)
        );
    }

    public function testGetMassNotificationEmailTemplateName(): void
    {
        $this->configManager
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['oro_notification.mass_notification_template', false, false, null, self::TEMPLATE_NAME],
            ]);

        self::assertEquals(self::TEMPLATE_NAME, $this->model->getMassNotificationEmailTemplateName());
    }

    public function testGetMassNotificationRecipientEmailsWhenNoRecipientsWereSet(): void
    {
        $this->configManager
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['oro_notification.mass_notification_recipients', false, false, null, null],
            ]);

        self::assertEquals([], $this->model->getMassNotificationRecipientEmails());
    }

    public function testGetMassNotificationRecipientEmails(): void
    {
        $this->configManager
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['oro_notification.mass_notification_recipients', false, false, null, 'first@mail.com;second@mail.com'],
            ]);

        self::assertEquals(['first@mail.com', 'second@mail.com'], $this->model->getMassNotificationRecipientEmails());
    }
}
