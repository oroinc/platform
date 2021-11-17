<?php

namespace Oro\Bundle\NotificationBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Manager\EmailTemplateManager;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Sends email notification using template.
 */
class SendEmailNotificationTemplateProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ManagerRegistry $managerRegistry;

    private EmailTemplateManager $emailTemplateManager;

    public function __construct(ManagerRegistry $managerRegistry, EmailTemplateManager $emailTemplateManager)
    {
        $this->managerRegistry = $managerRegistry;
        $this->emailTemplateManager = $emailTemplateManager;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $this->getMessageBody($message);
        if (!$messageBody) {
            return MessageProcessorInterface::REJECT;
        }

        $recipient = $this->getRecipient($messageBody);
        if (!$recipient) {
            return self::REJECT;
        }

        $sentCount = $this->emailTemplateManager
            ->sendTemplateEmail(
                From::emailAddress($messageBody['from']),
                [$recipient],
                new EmailTemplateCriteria($messageBody['template'], $messageBody['templateEntity'] ?? null),
                $messageBody['templateParams'] ?? []
            );

        if (!$sentCount) {
            return self::REJECT;
        }

        return self::ACK;
    }

    private function getMessageBody(MessageInterface $message): array
    {
        $messageBody = array_merge(
            [
                'from' => null,
                'recipientUserId' => null,
                'template' => null,
                'templateEntity' => null,
                'templateParams' => [],
            ],
            JSON::decode($message->getBody())
        );

        if (empty($messageBody['from']) || empty($messageBody['recipientUserId'])
            || empty($messageBody['template'])) {
            $this->logger->critical(
                sprintf(
                    'Message properties %s were not expected to be empty',
                    implode(', ', ['from', 'recipientUserId', 'template'])
                )
            );

            return [];
        }

        if (!is_array($messageBody['templateParams'])) {
            $this->logger->critical('Message property "templateParams" was expected to be array');

            return [];
        }

        return $messageBody;
    }

    private function getRecipient(array $messageBody): ?EmailHolderInterface
    {
        $recipient = $this->managerRegistry
            ->getManagerForClass(User::class)
            ->getReference(User::class, $messageBody['recipientUserId']);

        if (!$recipient) {
            $this->logger->error(sprintf('User with id "%d" was not found', $messageBody['recipientUserId']));
        }

        return $recipient;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [Topics::SEND_NOTIFICATION_EMAIL_TEMPLATE];
    }
}
