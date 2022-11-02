<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Manager;

use Oro\Bundle\EmailBundle\Exception\EmailTemplateNotFoundException;
use Oro\Bundle\EmailBundle\Model\DTO\LocalizedTemplateDTO;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Provider\LocalizedTemplateProvider;
use Oro\Bundle\NotificationBundle\Exception\NotificationSendException;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationSender;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotification;
use Oro\Bundle\NotificationBundle\Model\TemplateMassNotification;
use Oro\Bundle\UserBundle\Entity\User;
use Psr\Log\LoggerInterface;

class EmailNotificationManagerTest extends \PHPUnit\Framework\TestCase
{
    private const TEMPLATE_NAME = 'template_name';
    private const TEMPLATE_ENTITY_NAME = 'Some/Entity';

    private const SUBJECT_ENGLISH = 'English subject';
    private const SUBJECT_FRENCH = 'French subject';
    private const SUBJECT_CUSTOM = 'Custom subject';
    private const CONTENT_ENGLISH = 'English content';
    private const CONTENT_FRENCH = 'French content';

    /** @var EmailNotificationSender|\PHPUnit\Framework\MockObject\MockObject */
    private $emailNotificationSender;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var LocalizedTemplateProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $localizedTemplateProvider;

    /** @var EmailNotificationManager */
    private $manager;

    protected function setUp(): void
    {
        $this->emailNotificationSender = $this->createMock(EmailNotificationSender::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->localizedTemplateProvider = $this->createMock(LocalizedTemplateProvider::class);

        $this->manager = new EmailNotificationManager(
            $this->emailNotificationSender,
            $this->logger,
            $this->localizedTemplateProvider
        );
    }

    public function testProcessSingle(): void
    {
        $entity = new User();
        $englishRecipient = (new User())->setEmail('english@mail.com');
        $frenchRecipient1 = (new User())->setEmail('french1@mail.com');
        $frenchRecipient2 = (new User())->setEmail('french2@mail.com');

        $notification = new TemplateEmailNotification(
            new EmailTemplateCriteria(self::TEMPLATE_NAME),
            [$englishRecipient, $frenchRecipient1, $frenchRecipient2],
            $entity
        );

        $englishEmailTemplateModel = (new EmailTemplateModel())
            ->setSubject(self::SUBJECT_ENGLISH)
            ->setContent(self::CONTENT_ENGLISH)
            ->setType(EmailTemplateModel::CONTENT_TYPE_HTML);

        $frenchEmailTemplateModel = (new EmailTemplateModel())
            ->setSubject(self::SUBJECT_FRENCH)
            ->setContent(self::CONTENT_FRENCH)
            ->setType(EmailTemplateModel::CONTENT_TYPE_HTML);

        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->with(
                $notification->getRecipients(),
                $notification->getTemplateCriteria(),
                ['entity' => $notification->getEntity()]
            )
            ->willReturn([
                (new LocalizedTemplateDTO($englishEmailTemplateModel))->addRecipient($englishRecipient),
                (new LocalizedTemplateDTO($frenchEmailTemplateModel))
                    ->addRecipient($frenchRecipient1)
                    ->addRecipient($frenchRecipient2),
            ]);

        $expectedEnglishNotification = new TemplateEmailNotification(
            new EmailTemplateCriteria(self::TEMPLATE_NAME),
            [$englishRecipient],
            $entity
        );

        $expectedFrenchNotification = new TemplateEmailNotification(
            new EmailTemplateCriteria(self::TEMPLATE_NAME),
            [$frenchRecipient1, $frenchRecipient2],
            $entity
        );

        $this->emailNotificationSender->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [$expectedEnglishNotification, $englishEmailTemplateModel],
                [$expectedFrenchNotification, $frenchEmailTemplateModel]
            );

        $this->manager->processSingle($notification, []);
    }

    public function testProcessSingleMassNotification(): void
    {
        $englishRecipient = (new User())->setEmail('english@mail.com');

        $sender = From::emailAddress('some@mail.com');
        $notification = new TemplateMassNotification(
            $sender,
            [$englishRecipient],
            new EmailTemplateCriteria(self::TEMPLATE_NAME),
            self::SUBJECT_CUSTOM
        );

        $englishEmailTemplateModel = (new EmailTemplateModel())
            ->setSubject(self::SUBJECT_ENGLISH)
            ->setContent(self::CONTENT_ENGLISH)
            ->setType(EmailTemplateModel::CONTENT_TYPE_HTML);

        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->with(
                $notification->getRecipients(),
                $notification->getTemplateCriteria(),
                ['entity' => $notification->getEntity()]
            )
            ->willReturn([
                (new LocalizedTemplateDTO($englishEmailTemplateModel))->addRecipient($englishRecipient),
            ]);

        $customSubjectEmailTemplateModel = (new EmailTemplateModel())
            ->setSubject(self::SUBJECT_CUSTOM)
            ->setContent(self::CONTENT_ENGLISH)
            ->setType(EmailTemplateModel::CONTENT_TYPE_HTML);

        $expectedNotification = new TemplateEmailNotification(
            $notification->getTemplateCriteria(),
            [$englishRecipient],
            null,
            $sender
        );

        $this->emailNotificationSender->expects($this->once())
            ->method('sendMass')
            ->with($expectedNotification, $customSubjectEmailTemplateModel);

        $this->manager->processSingle($notification, []);
    }

    public function testProcessSingleWhenExceptionIsThrown(): void
    {
        $englishRecipient = (new User())->setEmail('english@mail.com');

        $notification = new TemplateEmailNotification(
            new EmailTemplateCriteria(self::TEMPLATE_NAME),
            [$englishRecipient]
        );

        $exception = new EmailTemplateNotFoundException($notification->getTemplateCriteria());

        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->with(
                $notification->getRecipients(),
                $notification->getTemplateCriteria(),
                ['entity' => $notification->getEntity(), 'some' => true]
            )
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('An error occurred while processing notification', ['exception' => $exception]);

        $this->expectException(NotificationSendException::class);
        $this->manager->processSingle($notification, ['some' => true]);
    }

    public function testProcess(): void
    {
        $recipient = (new User())->setEmail('english@mail.com');

        $notification1 = new TemplateEmailNotification(new EmailTemplateCriteria(self::TEMPLATE_NAME), [$recipient]);
        $notification2 = new TemplateEmailNotification(
            new EmailTemplateCriteria(self::TEMPLATE_NAME, self::TEMPLATE_ENTITY_NAME),
            [$recipient]
        );

        $emailTemplateModel1 = (new EmailTemplateModel())
            ->setSubject(self::SUBJECT_ENGLISH)
            ->setContent(self::CONTENT_ENGLISH)
            ->setType(EmailTemplateModel::CONTENT_TYPE_HTML);

        $emailTemplateModel2 = (new EmailTemplateModel())
            ->setSubject(self::SUBJECT_CUSTOM)
            ->setContent(self::CONTENT_ENGLISH)
            ->setType(EmailTemplateModel::CONTENT_TYPE_HTML);

        $this->localizedTemplateProvider->expects($this->exactly(2))
            ->method('getAggregated')
            ->withConsecutive(
                [
                    $notification1->getRecipients(),
                    $notification1->getTemplateCriteria(),
                    ['entity' => $notification1->getEntity(), 'some' => true],
                ],
                [
                    $notification2->getRecipients(),
                    $notification2->getTemplateCriteria(),
                    ['entity' => $notification2->getEntity(), 'some' => true],
                ]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    (new LocalizedTemplateDTO($emailTemplateModel1))->addRecipient($recipient),
                ],
                [
                    (new LocalizedTemplateDTO($emailTemplateModel2))->addRecipient($recipient),
                ]
            );

        $expectedNotification1 = new TemplateEmailNotification($notification1->getTemplateCriteria(), [$recipient]);
        $expectedNotification2 = new TemplateEmailNotification($notification2->getTemplateCriteria(), [$recipient]);

        $this->emailNotificationSender->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [$expectedNotification1, $emailTemplateModel1],
                [$expectedNotification2, $emailTemplateModel2]
            );

        $this->manager->process([$notification1, $notification2], null, ['some' => true]);
    }

    public function testProcessWhenExceptionIsThrown(): void
    {
        $recipient = (new User())->setEmail('english@mail.com');
        $notification = new TemplateEmailNotification(new EmailTemplateCriteria(self::TEMPLATE_NAME), [$recipient]);

        $exception = new EmailTemplateNotFoundException($notification->getTemplateCriteria());

        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->with(
                $notification->getRecipients(),
                $notification->getTemplateCriteria(),
                ['entity' => $notification->getEntity()]
            )
            ->willThrowException($exception);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                ['An error occurred while processing notification', ['exception' => $exception]],
                [$this->matchesRegularExpression('/An error occurred while sending .* notification/')]
            );

        $this->manager->process([$notification], $logger, []);
    }
}
