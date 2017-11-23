<?php
namespace Oro\Component\MessageQueue\Job;

use Psr\Log\LoggerInterface;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * @deprecated since 2.6. Kept only to avoid "MessageProcessor was not found" error after update from old version
 */
class CalculateRootJobProgressProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /** @var string[] */
    private static $finishStatuses = [
        Job::STATUS_SUCCESS,
        Job::STATUS_FAILED,
        Job::STATUS_STALE
    ];

    /**
     * @param JobStorage $jobStorage
     * @param LoggerInterface $logger
     */
    public function __construct(
        JobStorage $jobStorage,
        LoggerInterface $logger
    ) {
        $this->jobStorage = $jobStorage;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        if (! isset($data['jobId'])) {
            $this->logger->critical('Got invalid message');

            return self::REJECT;
        }

        $job = $this->jobStorage->findJobById($data['jobId']);
        if (! $job) {
            $this->logger->critical(
                sprintf('Job was not found. id: "%s"', $data['jobId'])
            );

            return self::REJECT;
        }

        $this->calculate($job);

        return self::ACK;
    }

    /**
     * @param Job $job
     */
    private function calculate(Job $job)
    {
        $rootJob = $job->isRoot() ? $job : $job->getRootJob();
        $rootJob->setLastActiveAt(new \DateTime());
        $children = $rootJob->getChildJobs();
        $numberOfChildren = count($children);
        if (0 === $numberOfChildren) {
            return;
        }

        $processed = 0;
        foreach ($children as $child) {
            if (in_array($child->getStatus(), self::$finishStatuses, true)) {
                $processed++;
            }
        }

        $progress = round($processed / $numberOfChildren, 4);
        $this->jobStorage->saveJob($rootJob, function (Job $rootJob) use ($progress) {
            if ($progress !== $rootJob->getJobProgress()) {
                $rootJob->setJobProgress($progress);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return ['oro.message_queue.job.calculate_root_job_progress'];
    }
}
