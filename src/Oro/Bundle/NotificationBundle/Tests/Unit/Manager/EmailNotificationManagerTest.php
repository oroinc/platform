<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Manager;

use Oro\Bundle\EmailBundle\Exception\EmailTemplateNotFoundException;
use Oro\Bundle\EmailBundle\Factory\EmailModelFromEmailTemplateFactory;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTopic;
use Oro\Bundle\NotificationBundle\Async\Topic\SendMassEmailNotificationTopic;
use Oro\Bundle\NotificationBundle\Exception\NotificationSendException;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotification;
use Oro\Bundle\NotificationBundle\Model\TemplateMassNotification;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use PHPUnit\Framework\TestCase;

class EmailNotificationManagerTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private const TEMPLATE_NAME = 'template_name';
    private const TEMPLATE_ENTITY_NAME = 'Some/Entity';

    private const SUBJECT_ENGLISH = 'English subject';
    private const SUBJECT_FRENCH = 'French subject';
    private const SUBJECT_CUSTOM = 'Custom subject';
    private const CONTENT_ENGLISH = 'English content';
    private const CONTENT_FRENCH = 'French content';

    private MessageProducerInterface $messageProducer;
    private NotificationSettings $notificationSettings;
    private EmailModelFromEmailTemplateFactory $emailModelFromEmailTemplateFactory;
    private EmailNotificationManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->notificationSettings = $this->createMock(NotificationSettings::class);
        $this->emailModelFromEmailTemplateFactory = $this->createMock(EmailModelFromEmailTemplateFactory::class);

        $this->manager = new EmailNotificationManager(
            $this->messageProducer,
            $this->notificationSettings,
            $this->emailModelFromEmailTemplateFactory
        );

        $this->setUpLoggerMock($this->manager);
    }

    public function testProcessSingle(): void
    {
        $entity = new User();
        $englishRecipient = (new User())->setEmail('english@mail.com');
        $frenchRecipient = (new User())->setEmail('french@mail.com');

        $notification = new TemplateEmailNotification(
            new EmailTemplateCriteria(self::TEMPLATE_NAME),
            [$englishRecipient, $frenchRecipient],
            $entity
        );

        $sender = From::emailAddress('no-reply@example.com');
        $this->notificationSettings->expects(self::once())
            ->method('getSenderByScopeEntity')
            ->willReturn($sender);

        $englishEmailModel = (new EmailModel())
            ->setFrom($sender->toString())
            ->setTo([$englishRecipient->getEmail()])
            ->setSubject(self::SUBJECT_ENGLISH)
            ->setBody(self::CONTENT_ENGLISH)
            ->setType(EmailTemplateInterface::TYPE_HTML);

        $frenchEmailModel = (new EmailModel())
            ->setFrom($sender->toString())
            ->setTo([$frenchRecipient->getEmail()])
            ->setSubject(self::SUBJECT_FRENCH)
            ->setBody(self::CONTENT_FRENCH)
            ->setType(EmailTemplateInterface::TYPE_HTML);

        $this->emailModelFromEmailTemplateFactory->expects(self::exactly(2))
            ->method('createEmailModel')
            ->withConsecutive(
                [
                    $sender,
                    $englishRecipient,
                    $notification->getTemplateCriteria(),
                    ['entity' => $notification->getEntity()],
                ],
                [
                    $sender,
                    $frenchRecipient,
                    $notification->getTemplateCriteria(),
                    ['entity' => $notification->getEntity()],
                ],
            )
            ->willReturnOnConsecutiveCalls(
                $englishEmailModel,
                $frenchEmailModel
            );

        $this->messageProducer->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    SendEmailNotificationTopic::getName(),
                    [
                        'from' => $englishEmailModel->getFrom(),
                        'toEmail' => current($englishEmailModel->getTo()),
                        'subject' => $englishEmailModel->getSubject(),
                        'body' => $englishEmailModel->getBody(),
                        'contentType' => $englishEmailModel->getType() === 'html' ? 'text/html' : 'text/plain',
                    ],
                ],
                [
                    SendEmailNotificationTopic::getName(),
                    [
                        'from' => $frenchEmailModel->getFrom(),
                        'toEmail' => current($frenchEmailModel->getTo()),
                        'subject' => $frenchEmailModel->getSubject(),
                        'body' => $frenchEmailModel->getBody(),
                        'contentType' => $frenchEmailModel->getType() === 'html' ? 'text/html' : 'text/plain',
                    ],
                ],
            );

        $this->manager->processSingle($notification);
    }

    public function testProcessSingleMassNotification(): void
    {
        $englishRecipient = (new User())->setEmail('english@mail.com');
        $frenchRecipient = (new User())->setEmail('french@mail.com');
        $sender = From::emailAddress('no-reply@example.com');

        $notification = new TemplateMassNotification(
            $sender,
            [$englishRecipient, $frenchRecipient],
            new EmailTemplateCriteria(self::TEMPLATE_NAME),
            self::SUBJECT_CUSTOM
        );

        $this->notificationSettings->expects(self::never())
            ->method('getSender');

        $englishEmailModel = (new EmailModel())
            ->setFrom($sender->toString())
            ->setTo([$englishRecipient->getEmail()])
            ->setSubject(self::SUBJECT_ENGLISH)
            ->setBody(self::CONTENT_ENGLISH)
            ->setType(EmailTemplateInterface::TYPE_HTML);

        $frenchEmailModel = (new EmailModel())
            ->setFrom($sender->toString())
            ->setTo([$frenchRecipient->getEmail()])
            ->setSubject(self::SUBJECT_FRENCH)
            ->setBody(self::CONTENT_FRENCH)
            ->setType(EmailTemplateInterface::TYPE_HTML);

        $this->emailModelFromEmailTemplateFactory->expects(self::exactly(2))
            ->method('createEmailModel')
            ->withConsecutive(
                [
                    $sender,
                    $englishRecipient,
                    $notification->getTemplateCriteria(),
                    [],
                ],
                [
                    $sender,
                    $frenchRecipient,
                    $notification->getTemplateCriteria(),
                    [],
                ],
            )
            ->willReturnOnConsecutiveCalls(
                $englishEmailModel,
                $frenchEmailModel
            );

        $this->messageProducer->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    SendMassEmailNotificationTopic::getName(),
                    [
                        'from' => $englishEmailModel->getFrom(),
                        'toEmail' => current($englishEmailModel->getTo()),
                        'subject' => self::SUBJECT_CUSTOM,
                        'body' => $englishEmailModel->getBody(),
                        'contentType' => $englishEmailModel->getType() === 'html' ? 'text/html' : 'text/plain',
                    ],
                ],
                [
                    SendMassEmailNotificationTopic::getName(),
                    [
                        'from' => $frenchEmailModel->getFrom(),
                        'toEmail' => current($frenchEmailModel->getTo()),
                        'subject' => self::SUBJECT_CUSTOM,
                        'body' => $frenchEmailModel->getBody(),
                        'contentType' => $frenchEmailModel->getType() === 'html' ? 'text/html' : 'text/plain',
                    ],
                ],
            );

        $this->manager->processSingle($notification);
    }

    public function testProcessSingleWhenExceptionIsThrown(): void
    {
        $englishRecipient = (new User())->setEmail('english@mail.com');
        $params = ['some' => true];

        $notification = new TemplateEmailNotification(
            new EmailTemplateCriteria(self::TEMPLATE_NAME),
            [$englishRecipient]
        );

        $sender = From::emailAddress('no-reply@example.com');
        $this->notificationSettings->expects(self::once())
            ->method('getSender')
            ->willReturn($sender);

        $exception = new EmailTemplateNotFoundException($notification->getTemplateCriteria());

        $this->emailModelFromEmailTemplateFactory->expects(self::once())
            ->method('createEmailModel')
            ->with(
                $sender,
                $englishRecipient,
                $notification->getTemplateCriteria(),
                $params,
            )
            ->willThrowException($exception);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with('An error occurred while processing notification', ['exception' => $exception]);

        $this->expectException(NotificationSendException::class);

        $this->manager->processSingle($notification, $params);
    }

    public function testProcess(): void
    {
        $entity = new User();
        $englishRecipient = (new User())->setEmail('english@mail.com');
        $frenchRecipient = (new User())->setEmail('french@mail.com');
        $params = ['some' => true];
        $sender = From::emailAddress('no-reply@example.com');

        $notification1 = new TemplateEmailNotification(
            new EmailTemplateCriteria(self::TEMPLATE_NAME),
            [$englishRecipient]
        );
        $notification2 = new TemplateEmailNotification(
            new EmailTemplateCriteria(self::TEMPLATE_NAME, self::TEMPLATE_ENTITY_NAME),
            [$frenchRecipient],
            $entity,
            $sender
        );

        $this->notificationSettings->expects(self::once())
            ->method('getSender')
            ->willReturn($sender);

        $englishEmailModel = (new EmailModel())
            ->setFrom($sender->toString())
            ->setTo([$englishRecipient->getEmail()])
            ->setSubject(self::SUBJECT_ENGLISH)
            ->setBody(self::CONTENT_ENGLISH)
            ->setType(EmailTemplateInterface::TYPE_HTML);

        $frenchEmailModel = (new EmailModel())
            ->setFrom($sender->toString())
            ->setTo([$frenchRecipient->getEmail()])
            ->setSubject(self::SUBJECT_FRENCH)
            ->setBody(self::CONTENT_FRENCH)
            ->setType(EmailTemplateInterface::TYPE_HTML);

        $this->emailModelFromEmailTemplateFactory->expects(self::exactly(2))
            ->method('createEmailModel')
            ->withConsecutive(
                [
                    $sender,
                    $englishRecipient,
                    $notification1->getTemplateCriteria(),
                    $params,
                ],
                [
                    $sender,
                    $frenchRecipient,
                    $notification2->getTemplateCriteria(),
                    ['entity' => $notification2->getEntity()] + $params,
                ],
            )
            ->willReturnOnConsecutiveCalls(
                $englishEmailModel,
                $frenchEmailModel
            );

        $this->messageProducer->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    SendEmailNotificationTopic::getName(),
                    [
                        'from' => $englishEmailModel->getFrom(),
                        'toEmail' => current($englishEmailModel->getTo()),
                        'subject' => $englishEmailModel->getSubject(),
                        'body' => $englishEmailModel->getBody(),
                        'contentType' => $englishEmailModel->getType() === 'html' ? 'text/html' : 'text/plain',
                    ],
                ],
                [
                    SendEmailNotificationTopic::getName(),
                    [
                        'from' => $frenchEmailModel->getFrom(),
                        'toEmail' => current($frenchEmailModel->getTo()),
                        'subject' => $frenchEmailModel->getSubject(),
                        'body' => $frenchEmailModel->getBody(),
                        'contentType' => $frenchEmailModel->getType() === 'html' ? 'text/html' : 'text/plain',
                    ],
                ],
            );

        $this->manager->process([$notification1, $notification2], $params);
    }

    public function testProcessWhenExceptionIsThrown(): void
    {
        $entity = new User();
        $englishRecipient = (new User())->setEmail('english@mail.com');
        $frenchRecipient = (new User())->setEmail('french@mail.com');
        $params = ['some' => true];
        $sender = From::emailAddress('no-reply@example.com');

        $notification1 = new TemplateEmailNotification(
            new EmailTemplateCriteria(self::TEMPLATE_NAME),
            [$englishRecipient]
        );
        $notification2 = new TemplateEmailNotification(
            new EmailTemplateCriteria(self::TEMPLATE_NAME, self::TEMPLATE_ENTITY_NAME),
            [$frenchRecipient],
            $entity,
            $sender
        );

        $this->notificationSettings->expects(self::once())
            ->method('getSender')
            ->willReturn($sender);

        $englishEmailModel = (new EmailModel())
            ->setFrom($sender->toString())
            ->setTo([$englishRecipient->getEmail()])
            ->setSubject(self::SUBJECT_ENGLISH)
            ->setBody(self::CONTENT_ENGLISH)
            ->setType(EmailTemplateInterface::TYPE_HTML);

        $exception = new EmailTemplateNotFoundException($notification1->getTemplateCriteria());

        $this->emailModelFromEmailTemplateFactory->expects(self::exactly(2))
            ->method('createEmailModel')
            ->withConsecutive(
                [
                    $sender,
                    $englishRecipient,
                    $notification1->getTemplateCriteria(),
                    $params,
                ],
                [
                    $sender,
                    $frenchRecipient,
                    $notification2->getTemplateCriteria(),
                    ['entity' => $notification2->getEntity()] + $params,
                ],
            )
            ->willReturnOnConsecutiveCalls(
                $englishEmailModel,
                self::throwException($exception)
            );

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->withConsecutive(
                [
                    SendEmailNotificationTopic::getName(),
                    [
                        'from' => $englishEmailModel->getFrom(),
                        'toEmail' => current($englishEmailModel->getTo()),
                        'subject' => $englishEmailModel->getSubject(),
                        'body' => $englishEmailModel->getBody(),
                        'contentType' => $englishEmailModel->getType() === 'html' ? 'text/html' : 'text/plain',
                    ],
                ],
            );

        $this->loggerMock->expects(self::exactly(2))
            ->method('error')
            ->withConsecutive(
                ['An error occurred while processing notification', ['exception' => $exception]],
                [self::matchesRegularExpression('/An error occurred while sending .* notification/')]
            );

        $this->manager->process([$notification1, $notification2], $params);
    }
}
