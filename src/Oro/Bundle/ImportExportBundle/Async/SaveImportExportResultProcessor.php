<?php

namespace Oro\Bundle\ImportExportBundle\Async;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\Topic\SaveImportExportResultTopic;
use Oro\Bundle\ImportExportBundle\Manager\ImportExportResultManager;
use Oro\Bundle\MessageQueueBundle\Entity\Job as JobEntity;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Responsible for processing the results of import or export before they are stored
 */
class SaveImportExportResultProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private ImportExportResultManager $importExportResultManager;

    private DoctrineHelper $doctrineHelper;

    private LoggerInterface $logger;

    public function __construct(
        ImportExportResultManager $importExportResultManager,
        DoctrineHelper $doctrineHelper,
        LoggerInterface $logger
    ) {
        $this->importExportResultManager = $importExportResultManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageBody = $message->getBody();

        $job = $this->getJobRepository()->findJobById($messageBody['jobId']);
        if ($job === null) {
            $this->logger->critical('Job not found');

            return self::REJECT;
        }

        $job = $job->isRoot() ? $job : $job->getRootJob();

        try {
            $this->saveJobResult($job, $messageBody);
        } catch (\Exception $e) {
            $this->logger->critical(
                sprintf('Unhandled error save result: %s', $e->getMessage()),
                ['exception' => $e]
            );
            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function saveJobResult(Job $job, array $parameters): void
    {
        $jobData = $job->getData();
        $this->importExportResultManager->saveResult(
            $job->getId(),
            $parameters['type'],
            $parameters['entity'],
            $parameters['owner'],
            $jobData['file'] ?? null,
            $parameters['options']
        );
    }

    public static function getSubscribedTopics(): array
    {
        return [SaveImportExportResultTopic::getName()];
    }

    private function getJobRepository(): JobRepository
    {
        return $this->doctrineHelper->getEntityRepository(JobEntity::class);
    }
}
