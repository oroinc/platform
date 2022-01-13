<?php

namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\Topic\SendEmailTemplateTopic;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Tools\AggregatedEmailTemplatesSender;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Uses {@see AggregatedEmailTemplatesSender} to send localized emails to specified recipients using specified email
 * template and create {@see EmailUser} entities.
 */
class SendEmailTemplateProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ManagerRegistry $managerRegistry;

    private AggregatedEmailTemplatesSender $aggregatedEmailTemplatesSender;

    public function __construct(
        ManagerRegistry $managerRegistry,
        AggregatedEmailTemplatesSender $aggregatedEmailTemplatesSender
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->aggregatedEmailTemplatesSender = $aggregatedEmailTemplatesSender;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
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
            $this->aggregatedEmailTemplatesSender->send(
                $entity,
                $recipients,
                From::emailAddress($messageBody['from']),
                $messageBody['templateName']
            );
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
        $entity = $this->managerRegistry->getManagerForClass($entityClass)->find($entityClass, $entityId);
        if (!$entity) {
            $this->logger->error(
                sprintf('Could not find required entity with class "%s" and id "%s".', $entityClass, $entityId)
            );

            return null;
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [SendEmailTemplateTopic::getName()];
    }
}
