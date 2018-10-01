<?php

namespace Oro\Bundle\NotificationBundle\Model;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;
use Oro\Bundle\NotificationBundle\Exception\NotificationSendException;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Sends maintenance notification email to all recipients defined in system configuration
 * or to all active users if such configuration is not defined.
 */
class MassNotificationSender
{
    const MAINTENANCE_VARIABLE  = 'maintenance_message';
    const NOTIFICATION_LOG_TYPE = 'mass';

    /** @var EmailNotificationManager */
    protected $emailNotificationManager;

    /** @var NotificationSettings */
    protected $notificationSettings;

    /** @var EntityManager */
    protected $em;

    /** @var EntityPool */
    protected $entityPool;

    /** @var DQLNameFormatter */
    protected $dqlNameFormatter;

    /**
     * @param EmailNotificationManager $emailNotificationManager
     * @param NotificationSettings     $notificationSettings
     * @param EntityManager            $em
     * @param EntityPool               $entityPool
     * @param DQLNameFormatter         $dqlNameFormatter
     */
    public function __construct(
        EmailNotificationManager $emailNotificationManager,
        NotificationSettings $notificationSettings,
        EntityManager $em,
        EntityPool $entityPool,
        DQLNameFormatter $dqlNameFormatter
    ) {
        $this->emailNotificationManager = $emailNotificationManager;
        $this->notificationSettings = $notificationSettings;
        $this->em = $em;
        $this->entityPool = $entityPool;
        $this->dqlNameFormatter = $dqlNameFormatter;
    }

    /**
     * @param string $body
     * @param string|null $subject
     * @param From|null $sender
     * @return int
     * @throws NotificationSendException
     */
    public function send($body, $subject = null, From $sender = null)
    {
        $sender = $sender ?? $this->notificationSettings->getSender();
        $recipients = $this->getRecipients();

        $massNotification = new TemplateMassNotification(
            $sender,
            $recipients,
            new EmailTemplateCriteria($this->notificationSettings->getMassNotificationEmailTemplateName()),
            $subject
        );

        $this->emailNotificationManager->process([$massNotification], null, [self::MAINTENANCE_VARIABLE => $body]);
        //persist and flush sending job entity
        $this->entityPool->persistAndFlush($this->em);

        return count($recipients);
    }

    /**
     * @return EmailHolderInterface[]
     */
    private function getRecipients(): array
    {
        $recipients = [];
        $recipientEmails = $this->notificationSettings->getMassNotificationRecipientEmails();
        if ($recipientEmails) {
            foreach ($recipientEmails as $recipientEmail) {
                $recipients[] = new EmailAddressWithContext($recipientEmail);
            }

            return $recipients;
        }

        foreach ($this->getUserRepository()->findEnabledUserEmails() as $item) {
            $recipients[] = new EmailAddressWithContext(
                $item['email'],
                $this->em->getReference(User::class, $item['id'])
            );
        }

        return $recipients;
    }

    /**
     * @return UserRepository
     */
    private function getUserRepository()
    {
        return $this->em->getRepository('OroUserBundle:User');
    }
}
