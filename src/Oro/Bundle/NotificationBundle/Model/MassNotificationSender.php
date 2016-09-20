<?php

namespace Oro\Bundle\NotificationBundle\Model;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;

class MassNotificationSender
{
    const MAINTENANCE_VARIABLE = 'maintenance_message';

    const NOTIFICATION_LOG_TYPE = 'mass';

    /**
     * @var EmailNotificationManager
     */
    protected $emailNotificationManager;

    /** @var ConfigManager */
    protected $cm;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EntityPool
     */
    protected $entityPool;

    /** @var DQLNameFormatter */
    protected $dqlNameFormatter;

    /**
     * @param EmailNotificationManager $emailNotificationManager
     * @param ConfigManager $cm
     * @param EntityManager $em
     * @param EntityPool $entityPool
     * @param DQLNameFormatter $dqlNameFormatter
     */
    public function __construct(
        EmailNotificationManager $emailNotificationManager,
        ConfigManager $cm,
        EntityManager $em,
        EntityPool $entityPool,
        DQLNameFormatter $dqlNameFormatter
    ) {
        $this->emailNotificationManager = $emailNotificationManager;
        $this->cm = $cm;
        $this->em = $em;
        $this->entityPool = $entityPool;
        $this->dqlNameFormatter = $dqlNameFormatter;
    }

    /**
     * @param string $body
     * @param string|null $subject
     * @param string|null $senderEmail
     * @param string|null $senderName
     * @return int
     */
    public function send(
        $body,
        $subject = null,
        $senderEmail = null,
        $senderName = null
    ) {
        $senderName  = $senderName ?: $senderEmail ?: $this->cm->get('oro_notification.email_notification_sender_name');
        $senderEmail = $senderEmail ?: $this->cm->get('oro_notification.email_notification_sender_email');

        $recipients = $this->getRecipientEmails();
        $template = $this->getTemplate($subject);

        $massNotification = new MassNotification($senderName, $senderEmail, $recipients, $template);

        $this->emailNotificationManager->process(
            null,
            [$massNotification],
            null,
            [self::MAINTENANCE_VARIABLE => $body]
        );
        //persist and flush sending job entity
        $this->entityPool->persistAndFlush($this->em);

        $recipientsCount = count($recipients);

        return $recipientsCount;
    }

    /**
     * Get template to use for notification
     *
     * @param string $subject
     * @return EmailTemplate
     */
    protected function getTemplate($subject)
    {
        $templateName = $this->cm->get('oro_notification.mass_notification_template');
        $template = $this->getEmailTemplateRepository()->findByName($templateName);
        $templateModel = new EmailTemplate();
        if ($template) {
            /* convert template entity into template model */
            $templateModel->setType($template->getType())
                ->setContent($template->getContent())
                ->setSubject($template->getSubject());
        } else {
            /* create simple txt template to send message in txt format */
            $templateModel->setType('txt');
            $templateModel->setContent(sprintf("{{ %s }}", self::MAINTENANCE_VARIABLE));
        }
        if ($subject) {
            $templateModel->setSubject($subject);
        }

        return $templateModel;
    }

    /**
     * @return array
     */
    protected function getRecipientEmails()
    {
        $recipients = $this->cm->get('oro_notification.mass_notification_recipients');
        if ($recipients) {
            $recipients = explode(';', $recipients);
        } else {
            $recipients = $this->getRecipientsFromDB();
        }

        return $recipients;
    }

    /**
     * Get all active users emails
     *
     * @return array
     */
    protected function getRecipientsFromDB()
    {
        $qb = $this->getUserRepository()->getPrimaryEmailsQb(
            $this->dqlNameFormatter->getFormattedNameDQL('u', 'Oro\Bundle\UserBundle\Entity\User')
        );
        $qb->andWhere('u.enabled = :enabled')->setParameter('enabled', true);
        $users = $qb->getQuery()->getResult();
        $users = array_map(function ($user) {
            return [$user['email'] => $user['name']];
        }, $users);

        return $users;
    }

    /**
     * @return EmailTemplateRepository
     */
    private function getEmailTemplateRepository()
    {
        return $this->em->getRepository('OroEmailBundle:EmailTemplate');
    }

    /**
     * @return UserRepository
     */
    private function getUserRepository()
    {
        return $this->em->getRepository('OroUserBundle:User');
    }
}
