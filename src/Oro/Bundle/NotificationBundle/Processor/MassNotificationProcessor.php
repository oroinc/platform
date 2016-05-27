<?php

namespace Oro\Bundle\NotificationBundle\Processor;

use Doctrine\ORM\EntityManager;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\NotificationBundle\Provider\Mailer\DbSpool;

class MassNotificationProcessor extends EmailNotificationProcessor
{
    const MAINTENANCE_VARIABLE = 'maintenance_message';

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

        $senderEmail = $senderEmail ?: $this->cm->get('oro_notification.email_notification_sender_email');
        $senderName  = $senderName ?: $this->cm->get('oro_notification.email_notification_sender_name');

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

        $this->addLogEntity();
        $massNotification = new MassNotification($senderName, $senderEmail, $recipients, $template);

        $this->process(null, [$massNotification], null, [self::MAINTENANCE_VARIABLE => $body]);

        $recipientsCount = count($recipients);

        return $recipientsCount;
    }

    /**
     * @inheritdoc
     */
    protected function addJob($command, $commandArgs = [])
    {
        if (!$this->hasNotFinishedJob($command)) {
            $job = $this->createJob($command, $commandArgs);
            $this->em->persist($job);
            $this->em->flush($job);
        }
    }

    /**
     * Add entity class to log email sending
     */
    protected function addLogEntity()
    {
        $tranport = $this->mailer->getTransport();
        if ($tranport instanceof \Swift_Transport_SpoolTransport) {
            $spool = $tranport->getSpool();
            if ($spool instanceof DbSpool) {
                $spool->setLogEntity('Oro\Bundle\NotificationBundle\Entity\MassNotification');
            }
        }
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
     * @return array
     */
    protected function getRecipientsFromDB()
    {
        return $this->em->getRepository('OroUserBundle:User')->getActiveUserEmails();
    }
}
