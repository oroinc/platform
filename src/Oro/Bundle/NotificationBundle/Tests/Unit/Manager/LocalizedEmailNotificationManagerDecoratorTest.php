<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Manager;

use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate as EmailTemplateEntity;
use Oro\Bundle\LocaleBundle\Provider\PreferredLanguageProviderInterface;
use Oro\Bundle\NotificationBundle\DependencyInjection\Configuration;
use Oro\Bundle\NotificationBundle\Manager\LocalizedEmailNotificationManagerDecorator;
use Oro\Bundle\NotificationBundle\Model\EmailNotification;
use Oro\Bundle\NotificationBundle\Model\EmailTemplate;
use Oro\Bundle\NotificationBundle\Model\MassNotification;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotification;
use Oro\Bundle\NotificationBundle\Model\TemplateMassNotification;
use Oro\Bundle\UserBundle\Entity\User;
use Psr\Log\LoggerInterface;

class LocalizedEmailNotificationManagerDecoratorTest extends \PHPUnit_Framework_TestCase
{
    private const PARAMS = ['some' => 'param'];
    private const TEMPLATE_NAME = 'template_name';
    private const TEMPLATE_ENTITY_NAME = 'Some/Entity';
    private const LANGUAGE_ENGLISH = 'en';
    private const LANGUAGE_FRENCH = 'fr_FR';
    private const SUBJECT_ENGLISH = 'English subject';
    private const SUBJECT_FRENCH = 'French subject';
    private const CONTENT_ENGLISH = 'English content';
    private const CONTENT_FRENCH = 'French content';

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var EmailNotificationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $manager;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var PreferredLanguageProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $languageProvider;

    /**
     * @var LocalizedEmailNotificationManagerDecorator
     */
    private $localizedEmailNotificationManagerDecorator;

    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->manager = $this->createMock(EmailNotificationManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->languageProvider = $this->createMock(PreferredLanguageProviderInterface::class);

        $this->localizedEmailNotificationManagerDecorator = new LocalizedEmailNotificationManagerDecorator(
            $this->manager,
            $this->doctrineHelper,
            $this->languageProvider,
            $this->logger
        );
    }

    public function testProcessWithoutTemplateEmailInterfaceNotifications(): void
    {
        $object = new User();
        $notifications = [new EmailNotification(new EmailTemplate()), new EmailNotification(new EmailTemplate())];
        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger **/
        $logger = $this->createMock(LoggerInterface::class);

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityRepositoryForClass');

        $this->languageProvider
            ->expects($this->never())
            ->method('getPreferredLanguage');

        $this->manager
            ->expects($this->once())
            ->method('process')
            ->with($object, $notifications, $logger, self::PARAMS);

        $this->localizedEmailNotificationManagerDecorator->process($object, $notifications, $logger, self::PARAMS);
    }

    public function testProcessWithTemplateEmailInterfaceNotificationsAndTemplateIsNotFoundWithCustomLogger(): void
    {
        $object = new User();
        $englishRecipient = (new User())->setEmail('english@mail.com');
        $frenchRecipient1 = (new User())->setEmail('french1@mail.com');
        $frenchRecipient2 = (new User())->setEmail('french2@mail.com');

        $notTemplateEmailNotificationInterface = new EmailNotification(new EmailTemplate());

        $emailTemplate = (new EmailTemplateEntity(self::TEMPLATE_NAME))->setEntityName(self::TEMPLATE_ENTITY_NAME);
        $notifications = [
            $notTemplateEmailNotificationInterface,
            new TemplateEmailNotification($emailTemplate, [$englishRecipient, $frenchRecipient1, $frenchRecipient2])
        ];

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger **/
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with(self::matchesRegularExpression('/^Could not find EmailTemplate/'));

        $emailTemplateRepository = $this->configureRepository();
        $emailTemplateRepository
            ->expects($this->once())
            ->method('findOneLocalized')
            ->with(new EmailTemplateCriteria(self::TEMPLATE_NAME, self::TEMPLATE_ENTITY_NAME))
            ->willReturn(null);

        $this->languageProvider
            ->expects($this->any())
            ->method('getPreferredLanguage')
            ->willReturnMap([
                [$frenchRecipient1, self::LANGUAGE_FRENCH],
                [$frenchRecipient2, self::LANGUAGE_FRENCH],
                [$englishRecipient, self::LANGUAGE_ENGLISH]
            ]);

        $expectedNotifications = [$notTemplateEmailNotificationInterface];

        $this->manager
            ->expects($this->once())
            ->method('process')
            ->with($object, $expectedNotifications, $logger, self::PARAMS);

        $this->localizedEmailNotificationManagerDecorator->process($object, $notifications, $logger, self::PARAMS);
    }

    public function testProcessWithTemplateEmailInterfaceNotificationsAndTemplateIsNotFoundWithInnerLogger(): void
    {
        $object = new User();
        $englishRecipient = (new User())->setEmail('english@mail.com');
        $frenchRecipient1 = (new User())->setEmail('french1@mail.com');
        $frenchRecipient2 = (new User())->setEmail('french2@mail.com');

        $notTemplateEmailNotificationInterface = new EmailNotification(new EmailTemplate());

        $emailTemplate = (new EmailTemplateEntity(self::TEMPLATE_NAME))->setEntityName(self::TEMPLATE_ENTITY_NAME);
        $notifications = [
            $notTemplateEmailNotificationInterface,
            new TemplateEmailNotification($emailTemplate, [$englishRecipient, $frenchRecipient1, $frenchRecipient2])
        ];

        $emailTemplateRepository = $this->configureRepository();
        $emailTemplateRepository
            ->expects($this->once())
            ->method('findOneLocalized')
            ->with(new EmailTemplateCriteria(self::TEMPLATE_NAME, self::TEMPLATE_ENTITY_NAME))
            ->willReturn(null);

        $this->languageProvider
            ->expects($this->any())
            ->method('getPreferredLanguage')
            ->willReturnMap([
                [$frenchRecipient1, self::LANGUAGE_FRENCH],
                [$frenchRecipient2, self::LANGUAGE_FRENCH],
                [$englishRecipient, self::LANGUAGE_ENGLISH]
            ]);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(self::matchesRegularExpression('/^Could not find EmailTemplate/'));

        $expectedNotifications = [$notTemplateEmailNotificationInterface];

        $this->manager
            ->expects($this->once())
            ->method('process')
            ->with($object, $expectedNotifications, null, self::PARAMS);

        $this->localizedEmailNotificationManagerDecorator->process($object, $notifications, null, self::PARAMS);
    }

    public function testProcessWithTemplateEmailInterfaceNotifications(): void
    {
        $object = new User();
        $englishRecipient = (new User())->setEmail('english@mail.com');
        $frenchRecipient1 = (new User())->setEmail('french1@mail.com');
        $frenchRecipient2 = (new User())->setEmail('french2@mail.com');

        $notTemplateEmailNotificationInterface = new EmailNotification(new EmailTemplate());

        $emailTemplate = (new EmailTemplateEntity(self::TEMPLATE_NAME))->setEntityName(self::TEMPLATE_ENTITY_NAME);

        $senderName = 'Sender';
        $senderEmail = 'sender@example.com';

        $templateEmailNotification = new TemplateEmailNotification(
            $emailTemplate,
            [
                $englishRecipient,
                $frenchRecipient1,
                $frenchRecipient2
            ]
        );
        $templateEmailNotification->setSenderName($senderName);
        $templateEmailNotification->setSenderEmail($senderEmail);
        $notifications = [
            $notTemplateEmailNotificationInterface,
            $templateEmailNotification
        ];

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger **/
        $logger = $this->createMock(LoggerInterface::class);

        $templateCriteria = new EmailTemplateCriteria(self::TEMPLATE_NAME, self::TEMPLATE_ENTITY_NAME);

        $englishEmailTemplate = (new EmailTemplateEntity(self::TEMPLATE_NAME, self::CONTENT_ENGLISH))
            ->setSubject(self::SUBJECT_ENGLISH);

        $frenchEmailTemplate = (new EmailTemplateEntity(self::TEMPLATE_NAME, self::CONTENT_FRENCH))
            ->setSubject(self::SUBJECT_FRENCH);

        $emailTemplateRepository = $this->configureRepository();
        $emailTemplateRepository
            ->expects($this->any())
            ->method('findOneLocalized')
            ->withConsecutive(
                [$templateCriteria, self::LANGUAGE_ENGLISH],
                [$templateCriteria, self::LANGUAGE_FRENCH]
            )
            ->willReturnOnConsecutiveCalls($englishEmailTemplate, $frenchEmailTemplate);

        $this->languageProvider
            ->expects($this->any())
            ->method('getPreferredLanguage')
            ->willReturnMap([
                [$frenchRecipient1, self::LANGUAGE_FRENCH],
                [$frenchRecipient2, self::LANGUAGE_FRENCH],
                [$englishRecipient, self::LANGUAGE_ENGLISH]
            ]);

        $englishEmailTemplateModel = (new EmailTemplate())
            ->setSubject(self::SUBJECT_ENGLISH)
            ->setContent(self::CONTENT_ENGLISH)
            ->setType('html');

        $frenchEmailTemplateModel = (new EmailTemplate())
            ->setSubject(self::SUBJECT_FRENCH)
            ->setContent(self::CONTENT_FRENCH)
            ->setType('html');

        $expectedNotifications = [
            $notTemplateEmailNotificationInterface,
            (new EmailNotification($englishEmailTemplateModel, ['english@mail.com']))
                ->setSenderEmail($senderEmail)
                ->setSenderName($senderName),
            (new EmailNotification($frenchEmailTemplateModel, ['french1@mail.com', 'french2@mail.com']))
                ->setSenderEmail($senderEmail)
                ->setSenderName($senderName),
        ];

        $this->manager
            ->expects($this->once())
            ->method('process')
            ->with($object, $expectedNotifications, $logger, self::PARAMS);

        $this->localizedEmailNotificationManagerDecorator->process($object, $notifications, $logger, self::PARAMS);
    }

    public function testProcessTemplateMassNotification(): void
    {
        $object = new User();
        $englishRecipient = (new User())->setEmail('english@mail.com');
        $frenchRecipient1 = (new User())->setEmail('french1@mail.com');
        $frenchRecipient2 = (new User())->setEmail('french2@mail.com');

        $emailTemplate = (new EmailTemplateEntity(self::TEMPLATE_NAME))->setEntityName(self::TEMPLATE_ENTITY_NAME);
        $notifications = [
            new TemplateMassNotification(
                'John Doe',
                'johndoe@example.com',
                [$englishRecipient, $frenchRecipient1, $frenchRecipient2],
                $emailTemplate,
                new EmailTemplateCriteria(
                    Configuration::DEFAULT_MASS_NOTIFICATION_TEMPLATE,
                    self::TEMPLATE_ENTITY_NAME
                )
            )
        ];

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger **/
        $logger = $this->createMock(LoggerInterface::class);

        $templateCriteria = new EmailTemplateCriteria(
            Configuration::DEFAULT_MASS_NOTIFICATION_TEMPLATE,
            self::TEMPLATE_ENTITY_NAME
        );

        $englishEmailTemplate = (new EmailTemplateEntity(self::TEMPLATE_NAME, self::CONTENT_ENGLISH))
            ->setSubject(self::SUBJECT_ENGLISH);

        $frenchEmailTemplate = (new EmailTemplateEntity(self::TEMPLATE_NAME, self::CONTENT_FRENCH))
            ->setSubject(self::SUBJECT_FRENCH);

        $emailTemplateRepository = $this->configureRepository();
        $emailTemplateRepository
            ->expects($this->any())
            ->method('findOneLocalized')
            ->withConsecutive(
                [$templateCriteria, self::LANGUAGE_ENGLISH],
                [$templateCriteria, self::LANGUAGE_FRENCH]
            )
            ->willReturnOnConsecutiveCalls($englishEmailTemplate, $frenchEmailTemplate);

        $this->languageProvider
            ->expects($this->any())
            ->method('getPreferredLanguage')
            ->willReturnMap([
                [$frenchRecipient1, self::LANGUAGE_FRENCH],
                [$frenchRecipient2, self::LANGUAGE_FRENCH],
                [$englishRecipient, self::LANGUAGE_ENGLISH]
            ]);

        $englishEmailTemplateModel = (new EmailTemplate())
            ->setSubject(self::SUBJECT_ENGLISH)
            ->setContent(self::CONTENT_ENGLISH)
            ->setType('html');

        $frenchEmailTemplateModel = (new EmailTemplate())
            ->setSubject(self::SUBJECT_FRENCH)
            ->setContent(self::CONTENT_FRENCH)
            ->setType('html');

        $expectedNotifications = [
            new MassNotification(
                'John Doe',
                'johndoe@example.com',
                ['english@mail.com'],
                $englishEmailTemplateModel
            ),
            new MassNotification(
                'John Doe',
                'johndoe@example.com',
                ['french1@mail.com', 'french2@mail.com'],
                $frenchEmailTemplateModel
            )
        ];

        $this->manager->expects($this->once())
            ->method('process')
            ->with($object, $expectedNotifications, $logger, self::PARAMS);

        $this->localizedEmailNotificationManagerDecorator->process($object, $notifications, $logger, self::PARAMS);
    }

    public function testProcessTemplateMassNotificationWithCustomSubject(): void
    {
        $object = new User();
        $recipient = (new User())->setEmail('example@mail.com');
        $subject = 'Custom subject';

        $emailTemplate = (new EmailTemplateEntity(self::TEMPLATE_NAME))->setEntityName(self::TEMPLATE_ENTITY_NAME);
        $notifications = [
            new TemplateMassNotification(
                'John Doe',
                'johndoe@example.com',
                [$recipient],
                $emailTemplate,
                new EmailTemplateCriteria(
                    Configuration::DEFAULT_MASS_NOTIFICATION_TEMPLATE,
                    self::TEMPLATE_ENTITY_NAME
                ),
                $subject
            )
        ];

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger **/
        $logger = $this->createMock(LoggerInterface::class);

        $templateCriteria = new EmailTemplateCriteria(
            Configuration::DEFAULT_MASS_NOTIFICATION_TEMPLATE,
            self::TEMPLATE_ENTITY_NAME
        );

        $englishEmailTemplate = (new EmailTemplateEntity(self::TEMPLATE_NAME, self::CONTENT_ENGLISH))
            ->setSubject(self::SUBJECT_ENGLISH);

        $emailTemplateRepository = $this->configureRepository();
        $emailTemplateRepository
            ->expects($this->any())
            ->method('findOneLocalized')
            ->with($templateCriteria, self::LANGUAGE_ENGLISH)
            ->willReturn($englishEmailTemplate);

        $this->languageProvider
            ->expects($this->any())
            ->method('getPreferredLanguage')
            ->with($recipient)
            ->willReturn(self::LANGUAGE_ENGLISH);

        $englishEmailTemplateModel = (new EmailTemplate())
            ->setSubject($subject)
            ->setContent(self::CONTENT_ENGLISH)
            ->setType('html');

        $expectedNotifications = [
            new MassNotification(
                'John Doe',
                'johndoe@example.com',
                ['example@mail.com'],
                $englishEmailTemplateModel
            )
        ];

        $this->manager->expects($this->once())
            ->method('process')
            ->with($object, $expectedNotifications, $logger, self::PARAMS);

        $this->localizedEmailNotificationManagerDecorator->process($object, $notifications, $logger, self::PARAMS);
    }

    private function configureRepository(): \PHPUnit_Framework_MockObject_MockObject
    {
        $emailTemplateRepository = $this->createMock(EmailTemplateRepository::class);

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->with(EmailTemplateEntity::class)
            ->willReturn($emailTemplateRepository);

        return $emailTemplateRepository;
    }
}
