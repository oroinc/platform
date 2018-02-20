<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\RuntimeException;

class DbalMessageQueueIsolator extends AbstractMessageQueueIsolator
{
    /** @var Filesystem */
    private $fs;

    /**
     * {@inheritdoc}
     */
    public function isApplicable(ContainerInterface $container)
    {
        return 'dbal' === $container->getParameter('message_queue_transport');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Dbal Message Queue';
    }

    protected function cleanUp()
    {
        $this->kernel->boot();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->kernel->getContainer()->get('doctrine');
        /** @var Connection $connection */
        $connection = $doctrine->getManager()->getConnection();

        $connection->executeQuery('DELETE FROM oro_message_queue');
        $connection->executeQuery('DELETE FROM oro_message_queue_job');
        $connection->executeQuery('DELETE FROM oro_message_queue_job_unique');

        $this->getFilesystem()
            ->remove(rtrim($this->kernel->getContainer()->getParameter('oro_message_queue.dbal.pid_file_dir')));
    }

    /**
     * {@inheritdoc}
     */
    public function waitWhileProcessingMessages($timeLimit = self::TIMEOUT)
    {
        while ($timeLimit > 0) {
            $isRunning = $this->ensureMessageQueueIsRunning();
            if (!$isRunning) {
                throw new RuntimeException('Message Queue is not running');
            }

            $isQueueEmpty = $this->isQueueEmpty();
            if ($isQueueEmpty) {
                return;
            }

            sleep(1);
            $timeLimit -= 1;
        }

        throw new RuntimeException('Message Queue was not process messages during time limit');
    }

    /**
     * @return bool
     */
    private function isQueueEmpty()
    {
        $this->kernel->boot();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->kernel->getContainer()->get('doctrine');
        /** @var Connection $connection */
        $connection = $doctrine->getManager()->getConnection();

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
     * @param string     $sqlQuery
     *
     * @return bool
     */
    private function hasRows(Connection $connection, $sqlQuery)
    {
        return 0 !== $connection->executeQuery($sqlQuery)->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    protected function getFilesystem()
    {
        if (!$this->fs) {
            $this->fs = new Filesystem();
        }

        return $this->fs;
    }
}
