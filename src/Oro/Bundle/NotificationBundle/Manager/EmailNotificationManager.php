<?php

namespace Oro\Bundle\NotificationBundle\Manager;

use Oro\Bundle\EmailBundle\Factory\EmailModelFromEmailTemplateFactory;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Model\SenderAwareInterface;
use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTopic;
use Oro\Bundle\NotificationBundle\Async\Topic\SendMassEmailNotificationTopic;
use Oro\Bundle\NotificationBundle\Exception\NotificationSendException;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotificationInterface;
use Oro\Bundle\NotificationBundle\Model\TemplateMassNotification;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Manager that processes notifications and make them to be processed using message queue.
 */
class EmailNotificationManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private MessageProducerInterface $messageProducer;

    private NotificationSettings $notificationSettings;

    private EmailModelFromEmailTemplateFactory $emailModelFromEmailTemplateFactory;

    public function __construct(
        MessageProducerInterface $messageProducer,
        NotificationSettings $notificationSettings,
        EmailModelFromEmailTemplateFactory $emailModelFromEmailTemplateFactory
    ) {
        $this->messageProducer = $messageProducer;
        $this->notificationSettings = $notificationSettings;
        $this->emailModelFromEmailTemplateFactory = $emailModelFromEmailTemplateFactory;

        $this->logger = new NullLogger();
    }

    /**
     * Sends the email notifications
     *
     * @param TemplateEmailNotificationInterface[] $notifications
     * @param array $params Additional params for template renderer
     * @param LoggerInterface|null $logger Override for default logger. If this parameter is specified
     *                                     this logger will be used instead of a logger specified
     *                                     in the constructor
     */
    public function process(array $notifications, array $params = [], LoggerInterface $logger = null): void
    {
        foreach ($notifications as $notification) {
            try {
                $this->processSingle($notification, $params, $logger);
            } catch (NotificationSendException $exception) {
                $logger = $logger ?? $this->logger;
                $logger->error(
                    sprintf(
                        'An error occurred while sending "%s" notification with email template "%s" for "%s" entity',
                        \get_class($notification),
                        $notification->getTemplateCriteria()->getName(),
                        $notification->getTemplateCriteria()->getEntityName()
                    ),
                    ['exception' => $exception]
                );
            }
        }
    }

    /**
     * @throws NotificationSendException
     */
    public function processSingle(
        TemplateEmailNotificationInterface $notification,
        array $params = [],
        LoggerInterface $logger = null
    ): void {
        try {
            $sender = $this->getSender($notification);

            if ($notification instanceof TemplateMassNotification) {
                $topic = SendMassEmailNotificationTopic::getName();
                $subjectOverride = $notification->getSubject();
            } else {
                $topic = SendEmailNotificationTopic::getName();
                $subjectOverride = null;
            }

            if ($notification->getEntity() !== null) {
                $params = ['entity' => $notification->getEntity()] + $params;
            }

            foreach ($notification->getRecipients() as $recipient) {
                $emailModel = $this->emailModelFromEmailTemplateFactory->createEmailModel(
                    $sender,
                    $recipient,
                    $notification->getTemplateCriteria(),
                    $params
                );

                if ($subjectOverride !== null) {
                    $emailModel->setSubject($subjectOverride);
                }

                $this->asyncSendEmail($emailModel, $topic);
            }
        } catch (\Throwable $exception) {
            $logger = $logger ?? $this->logger;
            $logger->error('An error occurred while processing notification', ['exception' => $exception]);

            throw new NotificationSendException($notification);
        }
    }

    /**
     * @throws \Oro\Component\MessageQueue\Transport\Exception\Exception
     */
    private function asyncSendEmail(EmailModel $emailModel, string $topic): void
    {
        $messageParams = [
            'from' => $emailModel->getFrom(),
            'toEmail' => current($emailModel->getTo()),
            'subject' => $emailModel->getSubject(),
            'body' => $emailModel->getBody(),
            'contentType' => $emailModel->getType() === 'html' ? 'text/html' : 'text/plain',
        ];

        $this->messageProducer->send($topic, $messageParams);
    }

    private function getSender(TemplateEmailNotificationInterface $notification): From
    {
        $sender = null;
        if ($notification instanceof SenderAwareInterface) {
            $sender = $notification->getSender();
        }

        if ($sender === null) {
            $scope = $notification->getEntity();
            $sender = $scope
                ? $this->notificationSettings->getSenderByScopeEntity($scope)
                : $this->notificationSettings->getSender();
        }

        return $sender;
    }
}
