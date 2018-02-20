<?php
namespace Oro\Bundle\EmailBundle\Async;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

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

    /**
     * @param MessageProducerInterface $producer
     * @param JobRunner $jobRunner
     * @param LoggerInterface $logger
     */
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
        $data = JSON::decode($message->getBody());

        if (! isset($data['emailIds'], $data['targetClass'], $data['targetId']) || ! is_array($data['emailIds'])) {
            $this->logger->critical('Got invalid message');

            return self::REJECT;
        }

        asort($data['emailIds']);

        $jobName = sprintf(
            '%s:%s:%s:%s',
            'oro.email.add_association_to_emails',
            $data['targetClass'],
            $data['targetId'],
            md5(implode(',', $data['emailIds']))
        );

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            $jobName,
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
                            $this->producer->send(Topics::ADD_ASSOCIATION_TO_EMAIL, [
                                'emailId' => $id,
                                'targetClass' => $data['targetClass'],
                                'targetId' => $data['targetId'],
                                'jobId' => $child->getId(),
                            ]);
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
        return [Topics::ADD_ASSOCIATION_TO_EMAILS];
    }
}
