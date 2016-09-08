<?php
namespace Oro\Bundle\SearchBundle\Async;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class ReindexEntityMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @param IndexerInterface $indexer
     * @param JobRunner $jobRunner
     * @param MessageProducerInterface $producer
     */
    public function __construct(IndexerInterface $indexer, JobRunner $jobRunner, MessageProducerInterface $producer)
    {
        $this->indexer = $indexer;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $classes = JSON::decode($message->getBody());

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            Topics::REINDEX,
            function (JobRunner $jobRunner) use ($classes) {
                $entityClasses = $this->getClassesForReindex($classes);

                foreach ($entityClasses as $entityClass) {
                    $jobRunner->createDelayed(
                        sprintf('%s:%s', Topics::INDEX_ENTITY_TYPE, $entityClass),
                        function (JobRunner $jobRunner, Job $child) use ($entityClass) {
                            $this->producer->send(Topics::INDEX_ENTITY_TYPE, [
                                'entityClass' => $entityClass,
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
     * @param null|string|string[] $classes
     *
     * @return string[]
     */
    public function getClassesForReindex($classes)
    {
        if (! $classes) {
            $this->indexer->resetIndex();
            return $this->indexer->getClassesForReindex();
        }

        $classes = is_array($classes) ? $classes : [$classes];

        $entityClasses = [];
        foreach ($classes as $class) {
            $entityClasses = array_merge($entityClasses, $this->indexer->getClassesForReindex($class));
        }

        $entityClasses = array_unique($entityClasses);

        foreach ($entityClasses as $entityClass) {
            $this->indexer->resetIndex($entityClass);
        }

        return $entityClasses;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::REINDEX];
    }
}
