<?php
namespace Oro\Bundle\EmailBundle\Async;

use Oro\Bundle\EmailBundle\Async\Topic\SendAutoResponsesTopic;
use Oro\Bundle\EmailBundle\Async\Topic\SendAutoResponseTopic;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Message queue processor that sends auto responses for multiple emails.
 */
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

    public function __construct(MessageProducerInterface $producer, JobRunner $jobRunner)
    {
        $this->producer = $producer;
        $this->jobRunner = $jobRunner;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = $message->getBody();

        asort($data['ids']);

        $result = $this->jobRunner->runUniqueByMessage(
            $message,
            function (JobRunner $jobRunner) use ($data) {
                foreach ($data['ids'] as $id) {
                    $jobRunner->createDelayed(
                        sprintf('%s:%s', 'oro.email.send_auto_response', $id),
                        function (JobRunner $jobRunner, Job $child) use ($id) {
                            $this->producer->send(
                                SendAutoResponseTopic::getName(),
                                [
                                    'id' => $id,
                                    'jobId' => $child->getId(),
                                ]
                            );
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
        return [SendAutoResponsesTopic::getName()];
    }
}
