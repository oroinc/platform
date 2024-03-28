<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Sender;

use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Psr\Log\Test\TestLogger;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;

/**
 * @dbIsolationPerTest
 */
class EmailTemplateSenderTest extends WebTestCase
{
    use MailerAssertionsTrait;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadUserData::class,
            '@OroEmailBundle/Tests/Functional/Sender/DataFixtures/EmailTemplateSender.yml',
        ]);
    }

    public function testSendEmailTemplateWhenNoTemplate(): void
    {
        $logger = new TestLogger();

        $emailTemplateSender = self::getContainer()->get('oro_email.sender.email_template_sender');
        $emailTemplateSender->setLogger($logger);

        /** @var NotificationSettings $notificationSettings */
        $notificationSettings = self::getContainer()->get('oro_notification.model.notification_settings');
        $user = $this->getReference(LoadUserData::SIMPLE_USER);

        $emailTemplateSender->sendEmailTemplate(
            $notificationSettings->getSender(),
            $user,
            'missing_template'
        );

        self::assertTrue(
            $logger->hasErrorThatContains(
                'Failed to send an email to {recipients_emails} using "{template_name}" email template: {message}'
            )
        );

        self::assertEmailCount(0);
    }

    public function testSendEmailTemplateWhenExtendedFromMissingEmailTemplate(): void
    {
        $logger = new TestLogger();

        $emailTemplateSender = self::getContainer()->get('oro_email.sender.email_template_sender');
        $emailTemplateSender->setLogger($logger);

        /** @var NotificationSettings $notificationSettings */
        $notificationSettings = self::getContainer()->get('oro_notification.model.notification_settings');
        $user = $this->getReference(LoadUserData::SIMPLE_USER);

        $emailTemplateSender->sendEmailTemplate(
            $notificationSettings->getSender(),
            $user,
            'email_template_extended_from_missing'
        );

        self::assertTrue(
            $logger->hasErrorThatContains(
                'Failed to send an email to {recipients_emails} using "{template_name}" email template: {message}'
            )
        );

        self::assertEmailCount(0);
    }

    public function testSendEmailTemplateWhenRegularEmailTemplate(): void
    {
        $logger = new TestLogger();

        $emailTemplateSender = self::getContainer()->get('oro_email.sender.email_template_sender');
        $emailTemplateSender->setLogger($logger);

        /** @var NotificationSettings $notificationSettings */
        $notificationSettings = self::getContainer()->get('oro_notification.model.notification_settings');
        $user = $this->getReference(LoadUserData::SIMPLE_USER);

        $emailTemplateSender->sendEmailTemplate(
            $notificationSettings->getSender(),
            $user,
            'email_template_regular',
            ['entity' => $user]
        );

        self::assertFalse($logger->hasErrorRecords(), 'Got records: ' . json_encode($logger->records));
        self::assertEmailCount(1);
        $mailerMessage = self::getMailerMessage();
        self::assertEmailSubjectContains($mailerMessage, 'Email Template Regular');
        self::assertEmailHtmlBodyContains($mailerMessage, 'Regular Template Content');
    }

    public function testSendEmailTemplateWhenRegularEmailTemplateAndLocalized(): void
    {
        $logger = new TestLogger();

        $emailTemplateSender = self::getContainer()->get('oro_email.sender.email_template_sender');
        $emailTemplateSender->setLogger($logger);

        /** @var NotificationSettings $notificationSettings */
        $notificationSettings = self::getContainer()->get('oro_notification.model.notification_settings');
        $user = $this->getReference(LoadUserData::SIMPLE_USER);

        $localizationDe = $this->getReference('localization_de');

        $emailTemplateSender->sendEmailTemplate(
            $notificationSettings->getSender(),
            $user,
            new EmailTemplateCriteria('email_template_regular', User::class),
            ['entity' => $user],
            ['localization' => $localizationDe]
        );

        self::assertFalse($logger->hasErrorRecords(), 'Got records: ' . json_encode($logger->records));
        self::assertEmailCount(1);
        $mailerMessage = self::getMailerMessage();
        self::assertEmailSubjectContains($mailerMessage, 'Email Template (DE) Regular');
        self::assertEmailHtmlBodyContains($mailerMessage, 'Regular Template (DE) Content');
    }

    public function testSendEmailTemplateWhenEmailTemplateExtendedFromBase(): void
    {
        $logger = new TestLogger();

        $emailTemplateSender = self::getContainer()->get('oro_email.sender.email_template_sender');
        $emailTemplateSender->setLogger($logger);

        /** @var NotificationSettings $notificationSettings */
        $notificationSettings = self::getContainer()->get('oro_notification.model.notification_settings');
        $user = $this->getReference(LoadUserData::SIMPLE_USER);

        $emailTemplateSender->sendEmailTemplate(
            $notificationSettings->getSender(),
            $user,
            'email_template_extended_from_base',
            ['entity' => $user]
        );

        self::assertFalse($logger->hasErrorRecords(), 'Got records: ' . json_encode($logger->records));
        self::assertEmailCount(1);
        $mailerMessage = self::getMailerMessage();
        self::assertEmailSubjectContains($mailerMessage, 'Email Template Extended from Base');
        self::assertEmailHtmlBodyContains($mailerMessage, 'Base Template Content');
        self::assertEmailHtmlBodyContains($mailerMessage, 'Content of an email template extended from base template');
    }

    public function testSendEmailTemplateWhenEmailTemplateExtendedFromBaseAndLocalized(): void
    {
        $logger = new TestLogger();

        $emailTemplateSender = self::getContainer()->get('oro_email.sender.email_template_sender');
        $emailTemplateSender->setLogger($logger);

        /** @var NotificationSettings $notificationSettings */
        $notificationSettings = self::getContainer()->get('oro_notification.model.notification_settings');
        $user = $this->getReference(LoadUserData::SIMPLE_USER);

        $localizationDe = $this->getReference('localization_de');

        $emailTemplateSender->sendEmailTemplate(
            $notificationSettings->getSender(),
            $user,
            new EmailTemplateCriteria('email_template_extended_from_base', User::class),
            ['entity' => $user],
            ['localization' => $localizationDe]
        );

        self::assertFalse($logger->hasErrorRecords(), 'Got records: ' . json_encode($logger->records));
        self::assertEmailCount(1);
        $mailerMessage = self::getMailerMessage();
        self::assertEmailSubjectContains($mailerMessage, 'Email Template (DE) Extended from Base');
        self::assertEmailHtmlBodyContains($mailerMessage, 'Base Template (DE) Content');
        self::assertEmailHtmlBodyContains(
            $mailerMessage,
            'Content of an email template (DE) extended from base template'
        );
    }
}
