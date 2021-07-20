<?php

namespace Oro\Bundle\NotificationBundle\Manager;

use Oro\Bundle\EmailBundle\Model\SenderAwareInterface;
use Oro\Bundle\EmailBundle\Provider\LocalizedTemplateProvider;
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
    /** @var EmailNotificationSender */
    private $emailNotificationSender;

    /** @var LoggerInterface */
    private $logger;

    /** @var LocalizedTemplateProvider */
    private $localizedTemplateProvider;

    /**
     * EmailNotificationManager constructor.
     */
    public function __construct(
        EmailNotificationSender $emailNotificationSender,
        LoggerInterface $logger,
        LocalizedTemplateProvider $localizedTemplateProvider
    ) {
        $this->emailNotificationSender = $emailNotificationSender;
        $this->logger = $logger;
        $this->localizedTemplateProvider = $localizedTemplateProvider;
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
     * @throws NotificationSendException
     */
    public function processSingle(
        TemplateEmailNotificationInterface $notification,
        array $params = [],
        LoggerInterface $logger = null
    ): void {
        try {
            $sender = $notification instanceof SenderAwareInterface
                ? $notification->getSender()
                : null;

            $templateCollection = $this->localizedTemplateProvider->getAggregated(
                $notification->getRecipients(),
                $notification->getTemplateCriteria(),
                ['entity' => $notification->getEntity()] + $params
            );

            foreach ($templateCollection as $localizedTemplateDTO) {
                $languageNotification = new TemplateEmailNotification(
                    $notification->getTemplateCriteria(),
                    $localizedTemplateDTO->getRecipients(),
                    $notification->getEntity(),
                    $sender
                );

                $emailTemplate = $localizedTemplateDTO->getEmailTemplate();

                if ($notification instanceof TemplateMassNotification) {
                    if ($notification->getSubject()) {
                        $emailTemplate->setSubject($notification->getSubject());
                    }

                    $this->emailNotificationSender->sendMass($languageNotification, $emailTemplate);
                } else {
                    $this->emailNotificationSender->send($languageNotification, $emailTemplate);
                }
            }
        } catch (\Exception $exception) {
            $logger = $logger ?? $this->logger;
            $logger->error('An error occurred while processing notification', ['exception' => $exception]);

            throw new NotificationSendException($notification);
        }
    }
}
