<?php

namespace Oro\Bundle\NotificationBundle\Model;

use Doctrine\ORM\EntityManager;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;
use Oro\Bundle\NotificationBundle\Processor\EmailNotificationProcessor;
use Oro\Bundle\NotificationBundle\Provider\Mailer\DbSpool;

class MassNotificationSender
{
    const MAINTENANCE_VARIABLE = 'maintenance_message';

    /**
     * @var EmailNotificationProcessor
     */
    protected $processor;

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
     * @param EmailNotificationProcessor $emailNotificationProcessor
     * @param ConfigManager $cm
     * @param EntityManager $em
     * @param EntityPool $entityPool
     * @param DQLNameFormatter $dqlNameFormatter
     */
    public function __construct(
        EmailNotificationProcessor $emailNotificationProcessor,
        ConfigManager $cm,
        EntityManager $em,
        EntityPool $entityPool,
        DQLNameFormatter $dqlNameFormatter
    ) {
        $this->processor = $emailNotificationProcessor;
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
        /** @var EmailTemplate $template */
        $template = $this->cm->get('oro_notification.mass_notification_template');
        $template = $this->em->getRepository('OroEmailBundle:EmailTemplate')->findByName($template);
        if (!$template) {
            $template = $this->initSimpleTemplate();
        }
        if ($subject) {
            $template->setSubject($subject);
        }
        $this->em->detach($template);
        $massNotification = new MassNotification($senderName, $senderEmail, $recipients, $template);

        $this->processor->addLogEntity('Oro\Bundle\NotificationBundle\Entity\MassNotification');
        $this->processor->setMessageLimit(0);
        $this->processor->process(null, [$massNotification], null, [self::MAINTENANCE_VARIABLE => $body]);
        //persist and flush sending job entity
        $this->entityPool->persistAndFlush($this->em);

        $recipientsCount = count($recipients);

        return $recipientsCount;
    }

    /**
     * Create simple txt template to send message in txt format
     *
     * @return EmailTemplate
     */
    protected function initSimpleTemplate()
    {
        $template = new EmailTemplate();
        $template->setContent(sprintf("{{ %s }}", self::MAINTENANCE_VARIABLE));
        $template->setType('txt');

        return $template;
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
        $qb = $this->em->getRepository('OroUserBundle:User')->getPrimaryEmailsQb(
            $this->dqlNameFormatter->getFormattedNameDQL('u', 'Oro\Bundle\UserBundle\Entity\User')
        );
        $qb->where('u.enabled = 1');
        $users = $qb->getQuery()->getResult();
        $users = array_map(function ($user) {
            return [$user['email'] => $user['name']];
        }, $users);

        return $users;
    }
}
