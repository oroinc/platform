<?php

namespace Oro\Bundle\NotificationBundle\Processor;

use Doctrine\ORM\EntityManager;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\NotificationBundle\Provider\Mailer\DbSpool;

class MassNotificationProcessor extends EmailNotificationProcessor
{
    /**
     * @param string            $body
     * @param string            $subject
     * @param string            $senderName
     * @param string            $senderEmail
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

        $template = $this->cm->get('oro_notification.mass_notification_template');
        $template = $this->em->getRepository('OroEmailBundle:EmailTemplate')->findByName($template);

        $massNotification = new MassNotification($senderName, $senderEmail, $recipients, $template);

        $tranport = $this->mailer->getTransport();
        if ($tranport instanceof \Swift_Transport_SpoolTransport) {
            $spool = $tranport->getSpool();
            if ($spool instanceof DbSpool) {
                $spool->setLogEntity('Oro\Bundle\NotificationBundle\Entity\MassNotification');
            }
        }
        
        $this->process(null, [$massNotification], null, ['maintenance_message' => $body]);
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
