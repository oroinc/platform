<?php

namespace Oro\Bundle\NotificationBundle\Processor;

use Doctrine\Common\Util\Inflector;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Types\Type;

use JMS\JobQueueBundle\Entity\Job;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

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

    /** @var  DateTimeFormatter */
    protected $dateTimeFormatter;

    /**
     * Constructor
     *
     * @param LoggerInterface   $logger
     * @param EntityManager     $em
     * @param EntityPool        $entityPool
     * @param EmailRenderer     $emailRenderer
     * @param \Swift_Mailer     $mailer
     * @param ConfigManager     $cm
     * @param DateTimeFormatter $dateTimeFormatter
     */
    public function __construct(
        LoggerInterface $logger,
        EntityManager $em,
        EntityPool $entityPool,
        EmailRenderer $emailRenderer,
        \Swift_Mailer $mailer,
        ConfigManager $cm,
        DateTimeFormatter $dateTimeFormatter
    ) {
        parent::__construct($logger, $em, $entityPool);
        $this->renderer          = $emailRenderer;
        $this->mailer            = $mailer;
        $this->cm                = $cm;
        $this->dateTimeFormatter = $dateTimeFormatter;
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
     */
    public function process($object, $notifications, LoggerInterface $logger = null)
    {
        if (!$logger) {
            $logger = $this->logger;
        }

        foreach ($notifications as $notification) {
            /** @var EmailTemplate $emailTemplate */
            $emailTemplate = $notification->getTemplate();
            $emailTemplate = $this->processDateTimeFields($emailTemplate, $object);

            try {
                list ($subjectRendered, $templateRendered) = $this->renderer->compileMessage(
                    $emailTemplate,
                    array('entity' => $object)
                );
            } catch (\Twig_Error $e) {
                $logger->error(
                    sprintf(
                        'Rendering of email template "%s"%s failed. %s',
                        $emailTemplate->getSubject(),
                        method_exists($emailTemplate, 'getId') ? sprintf(' (id: %d)', $emailTemplate->getId()) : '',
                        $e->getMessage()
                    ),
                    array('exception' => $e)
                );

                continue;
            }

            $senderEmail = $this->cm->get('oro_notification.email_notification_sender_email');
            $senderName  = $this->cm->get('oro_notification.email_notification_sender_name');
            $type        = $emailTemplate->getType() == 'txt' ? 'text/plain' : 'text/html';
            $recipients  = $notification->getRecipientEmails();
            foreach ((array)$recipients as $email) {
                $message = \Swift_Message::newInstance()
                    ->setSubject($subjectRendered)
                    ->setFrom($senderEmail, $senderName)
                    ->setTo($email)
                    ->setBody($templateRendered, $type);
                $this->mailer->send($message);
            }

            $this->addJob(self::SEND_COMMAND);
        }
    }

    /**
     * @param EmailTemplate $emailTemplate
     * @param               $object
     *
     * @return EmailTemplate
     */
    protected function processDateTimeFields(EmailTemplate $emailTemplate, $object)
    {
        $haveReplacements     = false;
        $emailTemplateContent = $emailTemplate->getContent();
        $emailTemplateSubject = $emailTemplate->getSubject();
        $entityMetadata       = $this->em->getClassMetadata(get_class($object));
        if ($entityMetadata) {
            $entityFieldMappings = $entityMetadata->fieldMappings;
            array_walk(
                $entityFieldMappings,
                function ($field) use ($object, &$emailTemplateContent, &$emailTemplateSubject, &$haveReplacements) {
                    if (in_array($field['type'], [Type::DATE, Type::TIME, Type::DATETIME, Type::DATETIMETZ])
                        && !empty($object->{Inflector::camelize('get_' . $field['fieldName'])}())
                    ) {
                        if (preg_match('/{{(\s|)entity.' . $field['fieldName'] . '(\s|)}}/', $emailTemplateContent)) {
                            $emailTemplateContent = preg_replace(
                                '/{{(\s|)entity.' . $field['fieldName'] . '(\s|)}}/',
                                $this->dateTimeFormatter->format(
                                    $object->{Inflector::camelize('get_' . $field['fieldName'])}()
                                ),
                                $emailTemplateContent
                            );
                            $haveReplacements     = true;
                        }

                        if (preg_match('/{{(\s|)entity.' . $field['fieldName'] . '(\s|)}}/', $emailTemplateSubject)) {
                            $emailTemplateSubject = preg_replace(
                                '/{{(\s|)entity.' . $field['fieldName'] . '(\s|)}}/',
                                $this->dateTimeFormatter->format(
                                    $object->{Inflector::camelize('get_' . $field['fieldName'])}()
                                ),
                                $emailTemplateSubject
                            );
                            $haveReplacements     = true;
                        }
                    }
                }
            );

            if ($haveReplacements) {
                $emailTemplate->setContent($emailTemplateContent);
                $emailTemplate->setSubject($emailTemplateSubject);
            }
        }

        return $emailTemplate;
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
