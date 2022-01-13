<?php

namespace Oro\Bundle\NotificationBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Manager\EmailTemplateManager;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTemplateTopic;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
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
        $messageBody = $message->getBody();

        $recipient = $this->getRecipient($messageBody);
        if (!$recipient) {
            return self::REJECT;
        }

        $sentCount = $this->emailTemplateManager
            ->sendTemplateEmail(
                From::emailAddress($messageBody['from']),
                [$recipient],
                new EmailTemplateCriteria($messageBody['template'], $messageBody['templateEntity']),
                $messageBody['templateParams']
            );

        if (!$sentCount) {
            return self::REJECT;
        }

        return self::ACK;
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
        return [SendEmailNotificationTemplateTopic::getName()];
    }
}
