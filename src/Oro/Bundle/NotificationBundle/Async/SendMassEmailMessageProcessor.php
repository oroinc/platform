<?php

namespace Oro\Bundle\NotificationBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\TemplateEmailMessageSender;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\NotificationBundle\Entity\SpoolItem;
use Oro\Bundle\NotificationBundle\Event\NotificationSentEvent;
use Oro\Bundle\NotificationBundle\Model\MassNotificationSender;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Sends maintenance (mass) notification message, e.g. `oro:maintenance-notification` command.
 * If body contains 'toEmail' property - single message will be sent to specified email,
 * otherwise the message will be sent to all active users.
 */
class SendMassEmailMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var DirectMailer */
    private $mailer;

    /** @var Processor */
    private $mailerProcessor;

    /** @var ManagerRegistry */
    private $managerRegistry;

    /** @var EmailRenderer */
    private $emailRenderer;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var LoggerInterface */
    private $logger;

    /** @var TemplateEmailMessageSender */
    private $templateEmailMessageSender;

    /**
     * @param DirectMailer               $mailer
     * @param Processor                  $processor
     * @param ManagerRegistry            $managerRegistry
     * @param EmailRenderer              $emailRenderer
     * @param LoggerInterface            $logger
     * @param EventDispatcherInterface   $eventDispatcher
     * @param TemplateEmailMessageSender $templateEmailMessageSender
     */
    public function __construct(
        DirectMailer $mailer,
        Processor $processor,
        ManagerRegistry $managerRegistry,
        EmailRenderer $emailRenderer,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        TemplateEmailMessageSender $templateEmailMessageSender
    ) {
        $this->mailer = $mailer;
        $this->mailerProcessor = $processor;
        $this->managerRegistry = $managerRegistry;
        $this->emailRenderer = $emailRenderer;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->templateEmailMessageSender = $templateEmailMessageSender;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        $data = array_merge([
            'sender'      => null,
            'fromName'    => null,
            'toEmail'     => null,
            'subject'     => null,
            'body'        => null,
            'contentType' => null,
            'template'    => null
        ], $data);

        if (empty($data['body'])
            || !isset($data['fromEmail'], $data['toEmail'])
            || (isset($data['template']) && !is_array($data['body']))
        ) {
            $this->logger->critical('Got invalid message');

            return self::REJECT;
        }

        $failedRecipients = [];
        if ($this->templateEmailMessageSender->isTranslatable($data)) {
            $result = $this->templateEmailMessageSender->sendTranslatedMessage($data, $failedRecipients);
        } else {
            if (isset($data['template'])) {
                list($data['subject'], $data['body']) = $this->renderTemplate($data['template'], $data['body']);
            }

            $emailMessage = new \Swift_Message(
                $data['subject'],
                $data['body'],
                $data['contentType']
            );
            $sender = From::fromArray($data['sender']);
            $sender->populate($emailMessage);
            $emailMessage->setTo($data['toEmail']);

            $this->mailerProcessor->processEmbeddedImages($emailMessage);

            // BAP-12503: can possibly send duplicate replies.
            $result = $this->mailer->send($emailMessage);

            $spoolItem = new SpoolItem();
            $spoolItem
                ->setLogType(MassNotificationSender::NOTIFICATION_LOG_TYPE)
                ->setMessage($emailMessage);
            $this->eventDispatcher->dispatch(
                NotificationSentEvent::NAME,
                new NotificationSentEvent($spoolItem, $result)
            );
        }

        if (!$result) {
            if (!empty($failedRecipients)) {
                $this->logger->error(
                    sprintf('Cannot send message to the following recipients: %s', print_r($failedRecipients, true))
                );
            } else {
                $this->logger->error('Cannot send message');
            }

            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::SEND_MASS_NOTIFICATION_EMAIL];
    }

    /**
     * @param string $templateName
     * @param array  $data
     *
     * @return array [{email subject} => {email message}]
     * @throws \RuntimeException
     */
    protected function renderTemplate($templateName, array $data)
    {
        $emailTemplate = $this->managerRegistry
            ->getManagerForClass(EmailTemplate::class)
            ->getRepository(EmailTemplate::class)
            ->findOneBy($templateName);

        if (!$emailTemplate instanceof EmailTemplateInterface) {
            throw new \RuntimeException(sprintf('EmailTemplate not found by name "%s"', $templateName));
        }

        return $this->emailRenderer->compileMessage($emailTemplate, $data);
    }
}
