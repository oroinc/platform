<?php

namespace Oro\Bundle\NotificationBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTemplateTopic;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotification;
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

    private EmailNotificationManager $emailNotificationManager;

    public function __construct(ManagerRegistry $managerRegistry, EmailNotificationManager $emailNotificationManager)
    {
        $this->managerRegistry = $managerRegistry;
        $this->emailNotificationManager = $emailNotificationManager;
        $this->logger = new NullLogger();
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $message->getBody();

        $recipient = $this->getRecipient($messageBody);
        if (!$recipient) {
            return self::REJECT;
        }

        $this->emailNotificationManager->processSingle(
            new TemplateEmailNotification(
                new EmailTemplateCriteria($messageBody['template'], $messageBody['templateEntity']),
                [$recipient],
                null,
                From::emailAddress($messageBody['from'])
            ),
            $messageBody['templateParams']
        );

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

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [SendEmailNotificationTemplateTopic::getName()];
    }
}
