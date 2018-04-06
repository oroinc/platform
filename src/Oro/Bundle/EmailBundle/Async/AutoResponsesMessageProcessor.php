<?php
namespace Oro\Bundle\EmailBundle\Async;

use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class AutoResponsesMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
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

        if (! isset($data['ids']) || ! is_array($data['ids'])) {
            $this->logger->critical('Got invalid message');

            return self::REJECT;
        }

        asort($data['ids']);
        $jobName = sprintf(
            '%s:%s',
            'oro.email.send_auto_responses',
            md5(implode(',', $data['ids']))
        );

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            $jobName,
            function (JobRunner $jobRunner) use ($data) {
                foreach ($data['ids'] as $id) {
                    $jobRunner->createDelayed(
                        sprintf('%s:%s', 'oro.email.send_auto_response', $id),
                        function (JobRunner $jobRunner, Job $child) use ($id) {
                            $this->producer->send(Topics::SEND_AUTO_RESPONSE, [
                                'id' => $id,
                                'jobId' => $child->getId(),
                            ]);
                        }
                    );
                }

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
        return [Topics::SEND_AUTO_RESPONSES];
    }
}
