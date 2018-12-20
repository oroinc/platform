<?php

namespace Oro\Bundle\NotificationBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Sends maintenance notification email to all recipients defined in system configuration
 * or to all active users if such configuration is not defined.
 */
class MassNotificationSender
{
    public const MAINTENANCE_VARIABLE  = 'maintenance_message';
    public const NOTIFICATION_LOG_TYPE = 'mass';

    /** @var EmailNotificationManager */
    private $emailNotificationManager;

    /** @var NotificationSettings */
    private $notificationSettings;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var EntityPool */
    private $entityPool;

    /**
     * @param EmailNotificationManager $emailNotificationManager
     * @param NotificationSettings     $notificationSettings
     * @param ManagerRegistry          $doctrine
     * @param EntityPool               $entityPool
     */
    public function __construct(
        EmailNotificationManager $emailNotificationManager,
        NotificationSettings $notificationSettings,
        ManagerRegistry $doctrine,
        EntityPool $entityPool
    ) {
        $this->emailNotificationManager = $emailNotificationManager;
        $this->notificationSettings = $notificationSettings;
        $this->doctrine = $doctrine;
        $this->entityPool = $entityPool;
    }

    /**
     * @param string      $body
     * @param string|null $subject
     * @param From|null   $sender
     *
     * @return int
     */
    public function send($body, $subject = null, From $sender = null)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManager();

        $sender = $sender ?? $this->notificationSettings->getSender();
        $recipients = $this->getRecipients($em);

        $massNotification = new TemplateMassNotification(
            $sender,
            $recipients,
            new EmailTemplateCriteria($this->notificationSettings->getMassNotificationEmailTemplateName()),
            $subject
        );

        $this->emailNotificationManager->process([$massNotification], null, [self::MAINTENANCE_VARIABLE => $body]);
        $this->entityPool->persistAndFlush($em);

        return count($recipients);
    }

    /**
     * @param EntityManagerInterface $em
     *
     * @return EmailHolderInterface[]
     */
    private function getRecipients(EntityManagerInterface $em): array
    {
        $recipients = [];
        $recipientEmails = $this->notificationSettings->getMassNotificationRecipientEmails();
        if ($recipientEmails) {
            foreach ($recipientEmails as $recipientEmail) {
                $recipients[] = new EmailAddressWithContext($recipientEmail);
            }

            return $recipients;
        }

        $enabledUserEmails = $em->getRepository(User::class)->findEnabledUserEmails();
        foreach ($enabledUserEmails as $item) {
            $recipients[] = new EmailAddressWithContext(
                $item['email'],
                $em->getReference(User::class, $item['id'])
            );
        }

        return $recipients;
    }
}
