<?php

namespace Oro\Bundle\WorkflowBundle\Async;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Async\Topic\ExecuteProcessJobTopic;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Process delayer process jobs.
 */
class ExecuteProcessJobProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    private DoctrineHelper $doctrineHelper;

    private ProcessHandler $processHandler;

    public function __construct(DoctrineHelper $doctrineHelper, ProcessHandler $processHandler)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->processHandler = $processHandler;
        $this->logger = new NullLogger();
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $message->getBody();

        $entityManager = $this->doctrineHelper->getEntityManager(ProcessJob::class, false);
        if (null === $entityManager) {
            $this->logger->critical(
                'Cannot get Entity Manager for {process_job_class}',
                ['process_job_class' => ProcessJob::class]
            );

            return self::REJECT;
        }

        /** @var ProcessJob $processJob */
        $processJob = $entityManager->find(ProcessJob::class, $messageBody['process_job_id']);
        if (!$processJob) {
            $this->logger->critical(
                'Process Job with id {process_job_id} not found',
                ['process_job_id' => $messageBody['process_job_id']]
            );

            return self::REJECT;
        }

        $entityManager->beginTransaction();
        try {
            try {
                $this->processHandler->handleJob($processJob);

                // Reload process job entity if it was detached during job handling.
                if (!$entityManager->contains($processJob)) {
                    $processJob = $entityManager->find(ProcessJob::class, $processJob->getId());
                }

                $entityManager->remove($processJob);
                $entityManager->flush();
            } finally {
                $this->processHandler->finishJob($processJob);
            }
            $entityManager->commit();
        } catch (\Exception $e) {
            $entityManager->rollback();

            $this->logger->error(
                'Unexpected exception occurred during queue message processing',
                [
                    'topic' => ExecuteProcessJobTopic::getName(),
                    'exception' => $e
                ]
            );

            throw $e;
        }

        return self::ACK;
    }

    public static function getSubscribedTopics(): array
    {
        return [ExecuteProcessJobTopic::getName()];
    }
}
