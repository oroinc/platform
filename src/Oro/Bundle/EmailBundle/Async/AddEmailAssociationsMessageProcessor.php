<?php
namespace Oro\Bundle\EmailBundle\Async;

use Oro\Bundle\EmailBundle\Async\Topic\AddEmailAssociationsTopic;
use Oro\Bundle\EmailBundle\Async\Topic\AddEmailAssociationTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Message queue processor that adds associations to multiple emails.
 */
class AddEmailAssociationsMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(MessageProducerInterface $producer, JobRunner $jobRunner, LoggerInterface $logger)
    {
        $this->producer = $producer;
        $this->jobRunner = $jobRunner;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = $message->getBody();

        asort($data['emailIds']);

        $result = $this->jobRunner->runUniqueByMessage(
            $message,
            function (JobRunner $jobRunner) use ($data) {
                foreach ($data['emailIds'] as $id) {
                    $jobRunner->createDelayed(
                        sprintf(
                            '%s:%s:%s:%s',
                            'oro.email.add_association_to_email',
                            $data['targetClass'],
                            $data['targetId'],
                            $id
                        ),
                        function (JobRunner $jobRunner, Job $child) use ($data, $id) {
                            $this->producer->send(
                                AddEmailAssociationTopic::getName(),
                                [
                                    'emailId' => $id,
                                    'targetClass' => $data['targetClass'],
                                    'targetId' => $data['targetId'],
                                    'jobId' => $child->getId(),
                                ]
                            );
                        }
                    );
                }

                $this->logger->info(
                    sprintf('Sent "%s" messages', count($data['emailIds'])),
                    ['data' => $data]
                );

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
        return [AddEmailAssociationsTopic::getName()];
    }
}
