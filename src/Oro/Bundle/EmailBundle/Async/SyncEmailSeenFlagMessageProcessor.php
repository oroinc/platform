<?php

namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Async\Topic\SyncEmailSeenFlagTopic;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository;
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
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var EmailFlagManager
     */
    private $emailFlagManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Registry $doctrine, EmailFlagManager $emailFlagManager, LoggerInterface $logger)
    {
        $this->doctrine = $doctrine;
        $this->emailFlagManager = $emailFlagManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = $message->getBody();

        /** @var EmailUser $emailUser */
        $emailUser = $this->getUserEmailRepository()->find($data['id']);
        if (! $emailUser) {
            $this->logger->error(
                sprintf('UserEmail was not found. id: "%s"', $data['id'])
            );

            return self::REJECT;
        }

        $this->emailFlagManager->changeStatusSeen($emailUser, $data['seen']);

        $emailUser->decrementUnsyncedFlagCount();

        $this->getUserEmailManager()->flush();

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [SyncEmailSeenFlagTopic::getName()];
    }

    /**
     * @return EmailUserRepository
     */
    private function getUserEmailRepository()
    {
        return $this->doctrine->getRepository(EmailUser::class);
    }

    /**
     * @return EntityManager
     */
    private function getUserEmailManager()
    {
        return $this->doctrine->getManagerForClass(EmailUser::class);
    }
}
