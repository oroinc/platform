<?php

namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\Topic\SendEmailTemplateTopic;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Sender\EmailTemplateSender;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Sends emails to specified recipients using specified email template and creates {@see EmailUser} entities.
 */
class SendEmailTemplateProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private ManagerRegistry $doctrine;
    private EmailTemplateSender $emailTemplateSender;
    private LoggerInterface $logger;

    public function __construct(
        ManagerRegistry $doctrine,
        EmailTemplateSender $emailTemplateSender,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->emailTemplateSender = $emailTemplateSender;
        $this->logger = $logger;
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [SendEmailTemplateTopic::getName()];
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $message->getBody();

        $recipients = $this->getRecipients($messageBody);
        if (!$recipients) {
            return self::REJECT;
        }

        $entity = $this->getEntity($messageBody);
        if (!$entity) {
            return self::REJECT;
        }

        try {
            foreach ($recipients as $recipient) {
                $this->emailTemplateSender->sendEmailTemplate(
                    From::emailAddress($messageBody['from']),
                    $recipient,
                    $messageBody['templateName'],
                    ['entity' => $entity]
                );
            }
        } catch (\Exception $exception) {
            $this->logger->error('Cannot send email template.', ['exception' => $exception]);

            return self::REJECT;
        }

        return self::ACK;
    }

    private function getRecipients(array $messageBody): array
    {
        $recipients = [];
        foreach ($messageBody['recipients'] as $recipient) {
            $recipients[] = new Recipient($recipient);
        }

        return $recipients;
    }

    private function getEntity(array $messageBody): ?object
    {
        [$entityClass, $entityId] = $messageBody['entity'];
        $entity = $this->doctrine->getManagerForClass($entityClass)->find($entityClass, $entityId);
        if (!$entity) {
            $this->logger->error(
                sprintf('Could not find required entity with class "%s" and id "%s".', $entityClass, $entityId)
            );

            return null;
        }

        return $entity;
    }
}
