<?php

namespace Oro\Bundle\EmailBundle\Async;

use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class UpdateEmailAssociationsMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var AssociationManager
     */
    private $associationManager;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param AssociationManager $associationManager
     * @param JobRunner $jobRunner
     * @param LoggerInterface $logger
     */
    public function __construct(AssociationManager $associationManager, JobRunner $jobRunner, LoggerInterface $logger)
    {
        $this->associationManager = $associationManager;
        $this->jobRunner = $jobRunner;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            'oro.email.update_associations_to_emails',
            function () {
                $this->associationManager->processUpdateAllEmailOwners();

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::UPDATE_ASSOCIATIONS_TO_EMAILS];
    }
}
