<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Processor;

use Doctrine\DBAL\Connection;
use Oro\Component\MessageQueue\Job\Job;
use Symfony\Bridge\Doctrine\RegistryInterface;
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

    /**
     * @param KernelInterface $kernel
     * @param MessageQueueProcessorInterface $baseMessageQueueProcessor
     */
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

        throw new \RuntimeException('Message Queue was not process messages during time limit');
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
        /** @var RegistryInterface $doctrine */
        $doctrine = $this->kernel->getContainer()->get('doctrine');
        $connection = $doctrine->getConnection();

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
        /** @var RegistryInterface $doctrine */
        $doctrine = $this->kernel->getContainer()->get('doctrine');
        /** @var Connection $connection */
        $connection = $doctrine->getConnection();

        return
            !$this->hasRows($connection, 'SELECT * FROM oro_message_queue')
            && !$this->hasRows(
                $connection,
                sprintf(
                    "SELECT * FROM oro_message_queue_job WHERE status NOT IN ('%s', '%s')",
                    Job::STATUS_SUCCESS,
                    Job::STATUS_FAILED
                )
            )
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
}
