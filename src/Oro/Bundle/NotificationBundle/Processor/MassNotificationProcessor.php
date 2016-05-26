<?php

namespace Oro\Bundle\NotificationBundle\Processor;

use Doctrine\ORM\EntityManager;

use JMS\JobQueueBundle\Entity\Job;

use Psr\Log\LoggerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;

class MassNotificationProcessor extends AbstractNotificationProcessor
{

    const SEND_COMMAND = 'swiftmailer:spool:send';

    /** @var EmailRenderer */
    protected $renderer;

    /** @var \Swift_Mailer */
    protected $mailer;

    /** @var string */
    protected $messageLimit = 100;

    /** @var ConfigManager */
    protected $cm;

    /** @var string */
    protected $env = 'prod';

    /** @var Processor */
    protected $processor;

    /**
     * Constructor
     *
     * @param LoggerInterface   $logger
     * @param EntityManager     $em
     * @param EntityPool        $entityPool
     * @param \Swift_Mailer     $mailer
     * @param ConfigManager     $cm
     * @param Processor         $processor
     */
    public function __construct(
        LoggerInterface $logger,
        EntityManager $em,
        EntityPool $entityPool,
        \Swift_Mailer $mailer,
        ConfigManager $cm,
        Processor $processor
    ) {
        parent::__construct($logger, $em, $entityPool);
        $this->mailer            = $mailer;
        $this->cm                = $cm;
        $this->processor         = $processor;
    }

    /**
     * @param string            $subject
     * @param string            $body
     * @param string            $sender
     * @param LoggerInterface   $logger
     */
    public function send($subject, $body, $sender = null, LoggerInterface $logger = null)
    {
        if (!$logger) {
            $logger = $this->logger;
        }

        $senderEmail = $sender ? $sender : $this->cm->get('oro_notification.email_notification_sender_email');
        $senderName  = $this->cm->get('oro_notification.email_notification_sender_name');

        foreach ($this->getRecipientEmails() as $email) {
            $message = \Swift_Message::newInstance()
                            ->setSubject($subject)
                            ->setFrom($senderEmail, $senderName)
                            ->setTo($email)
                            ->setBody($body);

            $this->mailer->send($message);
        }

        $this->addJob(self::SEND_COMMAND);
    }

    /**
     * Set message limit
     *
     * @param int $messageLimit
     */
    public function setMessageLimit($messageLimit)
    {
        $this->messageLimit = $messageLimit;
    }

    /**
     * Set environment
     *
     * @param string $env
     */
    public function setEnv($env)
    {
        $this->env = $env;
    }

    /**
     * Add swift mailer spool send task to job queue if it has not been added earlier
     *
     * @param string $command
     * @param array  $commandArgs
     *
     * @return Job
     */
    protected function createJob($command, $commandArgs = [])
    {
        $commandArgs = array_merge(
            [
                '--message-limit=' . $this->messageLimit,
                '--env=' . $this->env,
                '--mailer=db_spool_mailer',
            ],
            $commandArgs
        );

        return parent::createJob($command, $commandArgs);
    }

    /**
     * @return array
     */
    protected function getRecipientEmails()
    {
        $recipients = $this->cm->get('oro_notification.mass_notification_recipients');

        if (!$recipients) {
           return $this->getRecipientsFromDB();
        }

        return explode(';', $recipients);
    }

    /**
     * @return array
     */
    protected function getRecipientsFromDB()
    {
        return $this->em->getRepository('OroUserBundle:User')->getActiveUserEmails();
    }

}