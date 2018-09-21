<?php

namespace Oro\Bundle\NotificationBundle\Manager;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Provider\PreferredLanguageProviderInterface;
use Oro\Bundle\NotificationBundle\Model\EmailNotification;
use Oro\Bundle\NotificationBundle\Model\EmailNotificationInterface;
use Oro\Bundle\NotificationBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\NotificationBundle\Model\MassNotification;
use Oro\Bundle\NotificationBundle\Model\SenderAwareEmailNotificationInterface;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotificationInterface;
use Oro\Bundle\NotificationBundle\Model\TemplateMassNotification;
use Psr\Log\LoggerInterface;

/**
 * Processes notifications which implement TemplateEmailNotificationInterface and splits them into notifications with
 * localized email templates.
 */
class LocalizedEmailNotificationManagerDecorator extends EmailNotificationManager
{
    /**
     * @var EmailNotificationManager
     */
    private $manager;

    /**
     * @var PreferredLanguageProviderInterface
     */
    private $languageProvider;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param EmailNotificationManager $manager
     * @param DoctrineHelper $doctrineHelper
     * @param PreferredLanguageProviderInterface $languageProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        EmailNotificationManager $manager,
        DoctrineHelper $doctrineHelper,
        PreferredLanguageProviderInterface $languageProvider,
        LoggerInterface $logger
    ) {
        $this->manager = $manager;
        $this->doctrineHelper = $doctrineHelper;
        $this->languageProvider = $languageProvider;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process($object, $notifications, LoggerInterface $logger = null, $params = [])
    {
        $resultNotifications = [];
        foreach ($notifications as $notification) {
            if (!$notification instanceof TemplateEmailNotificationInterface) {
                $resultNotifications[] = $notification;
                continue;
            }

            $emailsGroups = $this->groupRecipientsByLanguage($notification->getRecipients());
            foreach ($emailsGroups as $language => $emailsGroup) {
                $localizedTemplate = $this->getLocalizedTemplate($notification, $language, $logger);

                if (!$localizedTemplate) {
                    break 2;
                }

                if ($notification instanceof TemplateMassNotification && $notification->getSubject()) {
                    $subject = $notification->getSubject();
                } else {
                    $subject = $localizedTemplate->getSubject();
                }

                $notificationEmailTemplate = new EmailTemplateModel();
                $notificationEmailTemplate
                    ->setType($localizedTemplate->getType())
                    ->setSubject($subject)
                    ->setContent($localizedTemplate->getContent());

                $resultNotifications[] = $this->createEmailNotification(
                    $notification,
                    $notificationEmailTemplate,
                    $emailsGroup
                );
            }
        }

        $this->manager->process($object, $resultNotifications, $logger, $params);
    }

    /**
     * @param EmailNotificationInterface $notification
     * @param EmailTemplateInterface $notificationEmailTemplate
     * @param array $emailsGroup
     * @return EmailNotificationInterface
     */
    private function createEmailNotification(
        EmailNotificationInterface $notification,
        EmailTemplateInterface $notificationEmailTemplate,
        array $emailsGroup
    ): EmailNotificationInterface {
        if ($notification instanceof TemplateMassNotification) {
            return new MassNotification(
                $notification->getSenderName(),
                $notification->getSenderEmail(),
                $emailsGroup,
                $notificationEmailTemplate
            );
        }

        $emailNotification = new EmailNotification($notificationEmailTemplate, $emailsGroup);

        if ($notification instanceof SenderAwareEmailNotificationInterface) {
            $emailNotification->setSenderEmail($notification->getSenderEmail());
            $emailNotification->setSenderName($notification->getSenderName());
        }

        return $emailNotification;
    }

    /**
     * @param TemplateEmailNotificationInterface $notification
     * @param string $language
     * @param LoggerInterface|null $logger
     * @return EmailTemplate|null
     * @throws \Doctrine\ORM\ORMException
     */
    private function getLocalizedTemplate(
        TemplateEmailNotificationInterface $notification,
        string $language,
        LoggerInterface $logger = null
    ): ?EmailTemplate {
        $criteria = $notification->getTemplateCriteria();

        $localizedTemplate = $this->doctrineHelper->getEntityRepositoryForClass(EmailTemplate::class)
            ->findOneLocalized($criteria, $language);

        if (!$localizedTemplate) {
            $logger = $logger ?? $this->logger;
            $logger->error(sprintf(
                'Could not find EmailTemplate with name "%s" and entityName "%s"',
                $criteria->getName(),
                $criteria->getEntityName()
            ));
        }

        return $localizedTemplate;
    }

    /**
     * @param array|EmailHolderInterface[] $recipients
     * @return array
     */
    private function groupRecipientsByLanguage(array $recipients): array
    {
        $groupedRecipients = [];
        foreach ($recipients as $recipient) {
            $groupedRecipients[$this->languageProvider->getPreferredLanguage($recipient)][] = $recipient->getEmail();
        }

        return $groupedRecipients;
    }
}
