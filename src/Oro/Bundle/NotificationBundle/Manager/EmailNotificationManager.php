<?php

namespace Oro\Bundle\NotificationBundle\Manager;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\SenderAwareInterface;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateContentProvider;
use Oro\Bundle\LocaleBundle\Provider\PreferredLanguageProviderInterface;
use Oro\Bundle\NotificationBundle\Exception\NotificationSendException;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotification;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotificationInterface;
use Oro\Bundle\NotificationBundle\Model\TemplateMassNotification;
use Psr\Log\LoggerInterface;

/**
 * Manager that processes notifications and make them to be processed using message queue.
 */
class EmailNotificationManager
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PreferredLanguageProviderInterface
     */
    private $languageProvider;

    /**
     * @var EmailTemplateContentProvider
     */
    private $emailTemplateContentProvider;

    /**
     * @var EmailNotificationSender
     */
    private $emailNotificationSender;

    /**
     * EmailNotificationManager constructor.
     *
     * @param EmailNotificationSender $emailNotificationSender
     * @param LoggerInterface $logger
     * @param EmailTemplateContentProvider $emailTemplateContentProvider
     * @param PreferredLanguageProviderInterface $languageProvider
     */
    public function __construct(
        EmailNotificationSender $emailNotificationSender,
        LoggerInterface $logger,
        EmailTemplateContentProvider $emailTemplateContentProvider,
        PreferredLanguageProviderInterface $languageProvider
    ) {
        $this->emailNotificationSender = $emailNotificationSender;
        $this->logger = $logger;
        $this->emailTemplateContentProvider = $emailTemplateContentProvider;
        $this->languageProvider = $languageProvider;
    }

    /**
     * Sends the email notifications
     *
     * @param TemplateEmailNotificationInterface[] $notifications
     * @param LoggerInterface $logger Override for default logger. If this parameter is specified
     *                                this logger will be used instead of a logger specified
     *                                in the constructor
     * @param array $params Additional params for template renderer
     */
    public function process(array $notifications, LoggerInterface $logger = null, array $params = []): void
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
     * @param TemplateEmailNotificationInterface $notification
     * @param array $params
     * @param LoggerInterface|null $logger
     * @throws NotificationSendException
     */
    public function processSingle(
        TemplateEmailNotificationInterface $notification,
        array $params = [],
        LoggerInterface $logger = null
    ): void {
        try {
            $recipientsGroups = $this->groupRecipientsByLanguage($notification->getRecipients());
            foreach ($recipientsGroups as $language => $recipients) {
                $emailTemplateModel = $this->emailTemplateContentProvider->getTemplateContent(
                    $notification->getTemplateCriteria(),
                    $language,
                    ['entity' => $notification->getEntity()] + $params
                );

                $languageNotification = $this->createLanguageNotification($notification, $recipients);

                if ($notification instanceof TemplateMassNotification) {
                    if ($notification->getSubject()) {
                        $emailTemplateModel->setSubject($notification->getSubject());
                    }

                    $this->emailNotificationSender->sendMass($languageNotification, $emailTemplateModel);
                } else {
                    $this->emailNotificationSender->send($languageNotification, $emailTemplateModel);
                }
            }
        } catch (\Exception $exception) {
            $logger = $logger ?? $this->logger;
            $logger->error('An error occurred while processing notification', ['exception' => $exception]);

            throw new NotificationSendException($notification);
        }
    }

    /**
     * @param array|EmailHolderInterface[] $recipients
     * @return array|EmailHolderInterface[]
     */
    private function groupRecipientsByLanguage(array $recipients): array
    {
        $groupedRecipients = [];
        foreach ($recipients as $recipient) {
            $groupedRecipients[$this->languageProvider->getPreferredLanguage($recipient)][] = $recipient;
        }

        return $groupedRecipients;
    }

    /**
     * @param TemplateEmailNotificationInterface $notification
     * @param iterable $recipients
     * @return TemplateEmailNotification
     */
    private function createLanguageNotification(
        TemplateEmailNotificationInterface $notification,
        iterable $recipients
    ): TemplateEmailNotification {
        $sender = $notification instanceof SenderAwareInterface
            ? $notification->getSender()
            : null;

        $languageNotification = new TemplateEmailNotification(
            $notification->getTemplateCriteria(),
            $recipients,
            $notification->getEntity(),
            $sender
        );

        return $languageNotification;
    }
}
