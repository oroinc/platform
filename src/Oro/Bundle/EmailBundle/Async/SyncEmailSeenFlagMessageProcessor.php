<?php

namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\Topic\SyncEmailSeenFlagTopic;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Manager\EmailFlagManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Message queue processor that synchronizes the "seen" flag of the specified email.
 */
class SyncEmailSeenFlagMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private ManagerRegistry $doctrine;
    private EmailFlagManager $emailFlagManager;
    private LoggerInterface $logger;

    public function __construct(
        ManagerRegistry $doctrine,
        EmailFlagManager $emailFlagManager,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->emailFlagManager = $emailFlagManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $data = $message->getBody();

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(EmailUser::class);
        /** @var EmailUser $emailUser */
        $emailUser = $em->find(EmailUser::class, $data['id']);
        if (! $emailUser) {
            $this->logger->error(
                sprintf('UserEmail was not found. id: "%s"', $data['id'])
            );

            return self::REJECT;
        }

        $this->emailFlagManager->changeStatusSeen($emailUser, $data['seen']);

        $emailUser->decrementUnsyncedFlagCount();

        $em->flush();

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [SyncEmailSeenFlagTopic::getName()];
    }
}
