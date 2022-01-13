<?php

namespace Oro\Bundle\EmailBundle\Async;

use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailOwnerAssociationTopic;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Message queue processor that updates email owner association.
 */
class UpdateEmailOwnerAssociationMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private AssociationManager $associationManager;

    private JobRunner $jobRunner;

    public function __construct(AssociationManager $associationManager, JobRunner $jobRunner)
    {
        $this->associationManager = $associationManager;
        $this->jobRunner = $jobRunner;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = $message->getBody();

        $result = $this->jobRunner->runDelayed($data['jobId'], function () use ($data) {
            $count = $this->associationManager->processUpdateEmailOwner($data['ownerClass'], [$data['ownerId']]);

            return (is_numeric($count));
        });

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [UpdateEmailOwnerAssociationTopic::getName()];
    }
}
