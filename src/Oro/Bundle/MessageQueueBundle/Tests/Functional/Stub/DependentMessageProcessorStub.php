<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Stub;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class DependentMessageProcessorStub implements MessageProcessorInterface, TopicSubscriberInterface
{
    const TEST_TOPIC = 'oro.message_queue.test_topic';
    const TEST_DEPENDENT_JOB_TOPIC = 'oro.message_queue.dependent_test_topic';
    const TEST_JOB_NAME = 'test_job_dependent|123456789';

    /** @var JobRunner */
    private $jobRunner;

    /** @var DependentJobService */
    private $dependentJobService;

    public function __construct(JobRunner $jobRunner, DependentJobService $dependentJobService)
    {
        $this->jobRunner = $jobRunner;
        $this->dependentJobService = $dependentJobService;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageBody = JSON::decode($message->getBody());

        if (false === is_array($messageBody)) {
            return self::REJECT;
        }

        $ownerId = $message->getMessageId();

        return $this->runUnique($messageBody, $ownerId) ? self::ACK : self::REJECT;
    }

    /**
     * @param array $messageBody
     * @param string $ownerId
     *
     * @return bool
     */
    private function runUnique(array $messageBody, $ownerId)
    {
        $jobName = $this->buildJobNameForMessage();
        $closure = function (JobRunner $jobRunner, Job $job) {
            $context = $this->dependentJobService->createDependentJobContext($job->getRootJob());
            $context->addDependentJob(
                self::TEST_DEPENDENT_JOB_TOPIC,
                [
                    'rootJobId' => $job->getRootJob()->getId(),
                ]
            );
            $this->dependentJobService->saveDependentJob($context);

            return true;
        };

        return $this->jobRunner->runUnique($ownerId, $jobName, $closure);
    }

    /**
     * @return string
     */
    private function buildJobNameForMessage()
    {
        return self::TEST_JOB_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [self::TEST_TOPIC];
    }
}
