<?php

namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Tools\AggregatedEmailTemplatesSender;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Uses {@see AggregatedEmailTemplatesSender} to send localized emails to specified recipients using specified email
 * template and create {@see EmailUser} entities.
 */
class SendEmailTemplateProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ManagerRegistry $managerRegistry;

    private ValidatorInterface $validator;

    private AggregatedEmailTemplatesSender $aggregatedEmailTemplatesSender;

    public function __construct(
        ManagerRegistry $managerRegistry,
        ValidatorInterface $validator,
        AggregatedEmailTemplatesSender $aggregatedEmailTemplatesSender
    ) {
        $this->validator = $validator;
        $this->managerRegistry = $managerRegistry;
        $this->aggregatedEmailTemplatesSender = $aggregatedEmailTemplatesSender;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = array_merge(
            ['from' => null, 'templateName' => null, 'recipients' => [], 'entity' => []],
            JSON::decode($message->getBody())
        );

        $from = $messageBody['from'];
        if (!$this->validateAddress($from)) {
            $this->logger->error(sprintf('Parameter "from" must contain a valid email address, got "%s".', $from));

            return self::REJECT;
        }

        $templateName = $messageBody['templateName'];
        if (!$templateName) {
            $this->logger->error('Parameter "templateName" must contain a valid template name.');

            return self::REJECT;
        }

        $recipients = $this->getRecipients($messageBody);
        if (!$recipients) {
            return self::REJECT;
        }

        $entity = $this->getEntity($messageBody);
        if (!$entity) {
            return self::REJECT;
        }

        try {
            $this->aggregatedEmailTemplatesSender->send($entity, $recipients, From::emailAddress($from), $templateName);
        } catch (\Exception $exception) {
            $this->logger->error('Cannot send email template.', ['exception' => $exception]);

            return self::REJECT;
        }

        return self::ACK;
    }

    private function getRecipients(array $messageBody): array
    {
        $recipients = [];
        if (is_array($messageBody['recipients'])) {
            foreach ($messageBody['recipients'] as $recipient) {
                if (!$this->validateAddress($recipient)) {
                    $this->logger->error(
                        sprintf('Parameter "recipients" must contain only valid email addresses, got "%s".', $recipient)
                    );

                    $recipients = [];
                    break;
                }

                $recipients[] = new Recipient($recipient);
            }
        }

        if (!$recipients) {
            $this->logger->error('Recipients list is empty');
        }

        return $recipients;
    }

    private function getEntity(array $messageBody): ?object
    {
        if (!is_array($messageBody['entity']) || count($messageBody['entity']) !== 2) {
            $this->logger->error(
                sprintf(
                    'Parameter "entity" must be an array [string $entityClass, int $entityId], got "%s".',
                    json_encode($messageBody['entity'])
                )
            );

            return null;
        }

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

    private function validateAddress(?string $email): bool
    {
        static $emailConstraint;
        if (!$emailConstraint) {
            $emailConstraint = new EmailConstraint();
        }

        $errorList = $this->validator->validate($email, $emailConstraint);

        return !$errorList->count();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [Topics::SEND_EMAIL_TEMPLATE];
    }
}
