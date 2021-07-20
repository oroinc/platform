<?php

namespace Oro\Bundle\EmailBundle\Async;

use Oro\Bundle\EmailBundle\Model\DTO\EmailAddressDTO;
use Oro\Bundle\EmailBundle\Tools\AggregatedEmailTemplatesSender;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Validator\Constraints\Email as EmailConstraints;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Sends emails based on passed templates.
 */
class SendEmailTemplateProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ValidatorInterface */
    private $validator;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var AggregatedEmailTemplatesSender */
    private $sender;

    /** @var EmailConstraints */
    private $emailConstraint;

    public function __construct(
        ValidatorInterface $validator,
        DoctrineHelper $doctrineHelper,
        AggregatedEmailTemplatesSender $sender
    ) {
        $this->validator = $validator;
        $this->doctrineHelper = $doctrineHelper;
        $this->sender = $sender;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $data = array_merge(
            ['from' => null, 'templateName' => null, 'recipients' => [], 'entity' => []],
            JSON::decode($message->getBody())
        );

        $from = $data['from'];
        if (!$this->validateAddress($from)) {
            $this->logger->error(sprintf('Parameter "from" must contain a valid email address, got "%s".', $from));

            return self::REJECT;
        }

        $templateName = $data['templateName'];
        if (!$templateName) {
            $this->logger->error('Parameter "templateName" must contain a valid template name.');

            return self::REJECT;
        }

        $recipients = $data['recipients'];
        foreach ($recipients as &$recipient) {
            if (!$this->validateAddress($recipient)) {
                $this->logger->error(
                    sprintf('Parameter "recipients" must contain only valid email addresses, got "%s".', $recipient)
                );

                return self::REJECT;
            }

            $recipient = new EmailAddressDTO($recipient);
        }

        @list($entityClass, $entityId) = $data['entity'];

        $entity = $this->doctrineHelper->getEntity($entityClass, $entityId);
        if (!$entity) {
            $this->logger->error(
                sprintf('Could not find required entity with class "%s" and id "%s".', $entityClass, $entityId)
            );

            return self::REJECT;
        }

        try {
            $this->sender->send($entity, $recipients, $from, $templateName);
        } catch (\Exception $exception) {
            $this->logger->error('Cannot send email template.', ['exception' => $exception]);

            return self::REJECT;
        }

        return self::ACK;
    }

    private function validateAddress(?string $email): bool
    {
        if (!$this->emailConstraint) {
            $this->emailConstraint = new EmailConstraints();
        }

        $errorList = $this->validator->validate($email, $this->emailConstraint);

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
