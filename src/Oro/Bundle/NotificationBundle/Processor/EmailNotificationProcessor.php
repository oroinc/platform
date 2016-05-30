<?php

namespace Oro\Bundle\NotificationBundle\Processor;

use Doctrine\ORM\EntityManager;

use JMS\JobQueueBundle\Entity\Job;

use Psr\Log\LoggerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;
use Oro\Bundle\NotificationBundle\Provider\Mailer\DbSpool;

class EmailNotificationProcessor extends AbstractNotificationProcessor
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
     * @param EmailRenderer     $emailRenderer
     * @param \Swift_Mailer     $mailer
     * @param ConfigManager     $cm
     * @param Processor         $processor
     */
    public function __construct(
        LoggerInterface $logger,
        EntityManager $em,
        EntityPool $entityPool,
        EmailRenderer $emailRenderer,
        \Swift_Mailer $mailer,
        ConfigManager $cm,
        Processor $processor
    ) {
        parent::__construct($logger, $em, $entityPool);
        $this->renderer          = $emailRenderer;
        $this->mailer            = $mailer;
        $this->cm                = $cm;
        $this->processor         = $processor;
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
     * Applies the given notifications to the given object
     *
     * @param mixed                        $object
     * @param EmailNotificationInterface[] $notifications
     * @param LoggerInterface              $logger Override for default logger. If this parameter is specified
     *                                             this logger will be used instead of a logger specified
     *                                             in the constructor
     * @param array                        $params Additional params for template renderer
     */
    public function process($object, $notifications, LoggerInterface $logger = null, $params = [])
    {
        if (!$logger) {
            $logger = $this->logger;
        }

        foreach ($notifications as $notification) {
            $emailTemplate = $notification->getTemplate();

            try {
                list ($subjectRendered, $templateRendered) = $this->renderer->compileMessage(
                    $emailTemplate,
                    ['entity' => $object] + $params
                );
            } catch (\Twig_Error $e) {
                $identity = method_exists($emailTemplate, '__toString')
                    ? (string)$emailTemplate : $emailTemplate->getSubject();

                $logger->error(
                    sprintf('Rendering of email template "%s" failed. %s', $identity, $e->getMessage()),
                    ['exception' => $e]
                );

                continue;
            }

            $senderEmail = $this->cm->get('oro_notification.email_notification_sender_email');
            $senderName  = $this->cm->get('oro_notification.email_notification_sender_name');
            if ($notification instanceof SenderAwareEmailNotificationInterface && $notification->getSenderEmail()) {
                $senderEmail = $notification->getSenderEmail();
                $senderName = $notification->getSenderName();
            }

            if ($emailTemplate->getType() == 'txt') {
                $type = 'text/plain';
            } else {
                $type = 'text/html';
            }

            foreach ((array)$notification->getRecipientEmails() as $email) {
                $message = \Swift_Message::newInstance()
                    ->setSubject($subjectRendered)
                    ->setFrom($senderEmail, $senderName)
                    ->setTo($email)
                    ->setBody($templateRendered, $type);
                $this->processor->processEmbeddedImages($message);
                $this->mailer->send($message);
            }

            $this->addJob(self::SEND_COMMAND);
        }
    }

    /**
     * Add entity class to log email sending
     *
     * @param string $className
     */
    public function addLogEntity($className)
    {
        $tranport = $this->mailer->getTransport();
        if ($tranport instanceof \Swift_Transport_SpoolTransport) {
            $spool = $tranport->getSpool();
            if ($spool instanceof DbSpool) {
                $spool->setLogEntity($className);
            }
        }
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
}
