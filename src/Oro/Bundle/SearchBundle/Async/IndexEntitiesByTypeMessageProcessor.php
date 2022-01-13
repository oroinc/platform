<?php

namespace Oro\Bundle\SearchBundle\Async;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByRangeTopic;
use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByTypeTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Message queue processor that indexes entities by class name.
 */
class IndexEntitiesByTypeMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    const BATCH_SIZE = 1000;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ManagerRegistry $doctrine,
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $payload = $message->getBody();

        $result = $this->jobRunner->runDelayed($payload['jobId'], function (JobRunner $jobRunner) use ($payload) {
            /** @var EntityManager $em */
            if (! $em = $this->doctrine->getManagerForClass($payload['entityClass'])) {
                $this->logger->error(
                    sprintf('Entity manager is not defined for class: "%s"', $payload['entityClass'])
                );

                return false;
            }

            $entityCount = $em->getRepository($payload['entityClass'])
                ->createQueryBuilder('entity')
                ->select('COUNT(entity)')
                ->getQuery()
                ->getSingleScalarResult()
            ;

            $batches = (int) ceil($entityCount / self::BATCH_SIZE);
            for ($i = 0; $i < $batches; $i++) {
                $jobRunner->createDelayed(
                    sprintf('%s:%s:%s', IndexEntitiesByRangeTopic::getName(), $payload['entityClass'], $i),
                    function (JobRunner $jobRunner, Job $child) use ($i, $payload) {
                        $this->producer->send(IndexEntitiesByRangeTopic::getName(), [
                            'entityClass' => $payload['entityClass'],
                            'offset' => $i * self::BATCH_SIZE,
                            'limit' => self::BATCH_SIZE,
                            'jobId' => $child->getId(),
                        ]);
                    }
                );
            }

            return true;
        });

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [IndexEntitiesByTypeTopic::getName()];
    }
}
