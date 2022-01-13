<?php

namespace Oro\Bundle\EmailBundle\Async;

use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Bundle\EmailBundle\Async\Topic\AddEmailAssociationTopic;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Message queue processor that adds an association to a single email.
 */
class AddEmailAssociationMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var AssociationManager
     */
    private $associationManager;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    public function __construct(
        AssociationManager $associationManager,
        JobRunner $jobRunner
    ) {
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
            $this->associationManager->processAddAssociation(
                [$data['emailId']],
                $data['targetClass'],
                $data['targetId']
            );

            return true;
        });

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [AddEmailAssociationTopic::getName()];
    }
}
