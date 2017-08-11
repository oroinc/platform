<?php

namespace Oro\Bundle\NotificationBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class SendEmailMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var DirectMailer
     */
    private $mailer;

    /**
     * @var Processor
     */
    private $mailerProcessor;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var EmailRenderer
     */
    private $emailRenderer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @param DirectMailer    $mailer
     * @param Processor       $processor
     * @param ManagerRegistry $managerRegistry
     * @param EmailRenderer   $emailRenderer
     * @param LoggerInterface $logger
     */
    public function __construct(
        DirectMailer $mailer,
        Processor $processor,
        ManagerRegistry $managerRegistry,
        EmailRenderer $emailRenderer,
        LoggerInterface $logger
    ) {
        $this->mailer = $mailer;
        $this->mailerProcessor = $processor;
        $this->managerRegistry = $managerRegistry;
        $this->emailRenderer = $emailRenderer;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        $data = array_merge([
            'fromEmail' => null,
            'fromName' => null,
            'toEmail' => null,
            'subject' => null,
            'body' => null,
            'contentType' => null,
            'template' => null
        ], $data);

        if (empty($data['body']) || ! isset($data['fromEmail'], $data['toEmail'])
            || (isset($data['template']) && ! is_array($data['body']))
        ) {
            $this->logger->critical('Got invalid message');

            return self::REJECT;
        }

        if (isset($data['template'])) {
            list($data['subject'], $data['body']) = $this->renderTemplate($data['template'], $data['body']);
        }

        $emailMessage = new \Swift_Message(
            $data['subject'],
            $data['body'],
            $data['contentType']
        );

        $emailMessage->setFrom($data['fromEmail'], $data['fromName']);
        $emailMessage->setTo($data['toEmail']);

        $this->mailerProcessor->processEmbeddedImages($emailMessage);

        //toDo: can possibly send duplicate replies. See BAP-12503

        if (! $this->mailer->send($emailMessage)) {
            $this->logger->error('Cannot send message');

            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::SEND_NOTIFICATION_EMAIL];
    }

    /**
     * @param string $templateName
     * @param array $data
     *
     * @return array - first element is email subject, second - message
     * @throws \RuntimeException
     */
    protected function renderTemplate($templateName, array $data)
    {
        $emailTemplate = $this->findEmailTemplateByName($templateName);

        if (! $emailTemplate instanceof EmailTemplateInterface) {
            throw new \RuntimeException(
                sprintf('[SendEmailMessageProcessor] EmailTemplate not found by name "%s"', $templateName)
            );
        }

        return $this->emailRenderer->compileMessage($emailTemplate, $data);
    }

    /**
     * @param string $emailTemplateName
     *
     * @return EmailTemplateInterface
     */
    protected function findEmailTemplateByName($emailTemplateName)
    {
        return $this->managerRegistry
            ->getManagerForClass(EmailTemplate::class)
            ->getRepository(EmailTemplate::class)
            ->findOneBy(['name' => $emailTemplateName]);
    }
}
