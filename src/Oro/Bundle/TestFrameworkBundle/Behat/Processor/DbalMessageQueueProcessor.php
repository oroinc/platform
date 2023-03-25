<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Processor;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Job\Job;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Message queue processor for DBAL transport
 */
class DbalMessageQueueProcessor implements MessageQueueProcessorInterface
{
    /** @var KernelInterface */
    private $kernel;

    /** @var MessageQueueProcessorInterface */
    private $baseMessageQueueProcessor;

    public function __construct(KernelInterface $kernel, MessageQueueProcessorInterface $baseMessageQueueProcessor)
    {
        $this->kernel = $kernel;
        $this->baseMessageQueueProcessor = $baseMessageQueueProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function startMessageQueue()
    {
        $this->baseMessageQueueProcessor->startMessageQueue();
    }

    /**
     * {@inheritdoc}
     */
    public function stopMessageQueue()
    {
        $this->baseMessageQueueProcessor->stopMessageQueue();
    }

    /**
     * {@inheritdoc}
     */
    public function waitWhileProcessingMessages($timeLimit = self::TIMEOUT)
    {
        $endTime = new \DateTime(sprintf('+%d seconds', $timeLimit));
        while (true) {
            $this->baseMessageQueueProcessor->waitWhileProcessingMessages();

            if ($this->isEmptyTables()) {
                return;
            }

            usleep(100000);

            $now = new \DateTime();
            if ($now >= $endTime) {
                break;
            }
        }

        $exception = new \RuntimeException(
            sprintf(
                'The message queue has not been able to finish processing messages within the last %d seconds.',
                $timeLimit
            )
        );

        $this->getLogger()->error(
            'Not processed messages list: {messages}, jobs: {jobs}, unique jobs: {uniqueJobs}',
            [
                'messages' => $this->getQueueMessages($this->getMessageQueueConnection()),
                'exception' => $exception,
                'jobs' => $this->getMessageQueueConnection()->executeQuery(
                    $this->getRunningJobsSql()
                )->fetchAllAssociative(),
                'uniqueJobs' => $this->getUniqueJobs($this->getMessageQueueConnection()),
            ]
        );

        throw $exception;
    }

    /**
     * {@inheritdoc}
     */
    public function isRunning()
    {
        return $this->baseMessageQueueProcessor->isRunning();
    }

    /**
     * {@inheritdoc}
     */
    public function cleanUp()
    {
        $this->baseMessageQueueProcessor->cleanUp();

        $connection = $this->getMessageQueueConnection();

        /** @var Filesystem $filesystem */
        $filesystem = $this->kernel->getContainer()->get('filesystem');
        $pidFileDir = $this->kernel->getContainer()->getParameter('oro_message_queue.dbal.pid_file_dir');

        $connection->executeQuery('DELETE FROM oro_message_queue');
        $connection->executeQuery('DELETE FROM oro_message_queue_job');
        $connection->executeQuery('DELETE FROM oro_message_queue_job_unique');

        $filesystem->remove(rtrim($pidFileDir));
    }

    /**
     * @return bool
     */
    private function isEmptyTables()
    {
        $connection = $this->getMessageQueueConnection();

        return
            !$this->hasRows($connection, 'SELECT * FROM oro_message_queue')
            && !$this->hasRows($connection, $this->getRunningJobsSql())
            && !$this->hasRows($connection, 'SELECT * FROM oro_message_queue_job_unique');
    }

    /**
     * @param Connection $connection
     * @param string $sqlQuery
     *
     * @return bool
     */
    private function hasRows(Connection $connection, $sqlQuery)
    {
        return 0 !== $connection->executeQuery($sqlQuery)->rowCount();
    }

    private function getRunningJobsSql(): string
    {
        return sprintf(
            "SELECT * FROM oro_message_queue_job WHERE status NOT IN ('%s', '%s', '%s')",
            Job::STATUS_SUCCESS,
            Job::STATUS_FAILED,
            Job::STATUS_CANCELLED
        );
    }


    private function getQueueMessages(Connection $connection): array
    {
        return $connection->executeQuery('SELECT * FROM oro_message_queue')->fetchAllAssociative();
    }

    private function getMessageQueueConnection(): Connection
    {
        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->kernel->getContainer()->get('doctrine');


        return $doctrine->getConnection('message_queue');
    }

    private function getLogger(): LoggerInterface
    {
        return $this->kernel->getContainer()->get('monolog.logger.consumer');
    }

    private function getUniqueJobs(Connection $connection)
    {
        return $connection->executeQuery('SELECT * FROM oro_message_queue_job_unique')->fetchAllAssociative();
    }
}
