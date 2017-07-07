<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\RuntimeException;

class DbalMessageQueueIsolator extends AbstractMessageQueueIsolator
{
    /** @var Filesystem */
    private $fs;

    /** {@inheritdoc} */
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
        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->kernel->getContainer()->get('doctrine');
        /** @var Connection $connection */
        $connection = $doctrine->getManager()->getConnection();
        /** @var Connection $connectionMQ */
        $connectionMQ = $doctrine->getManager('message_queue_job')->getConnection();

        $connection->executeQuery('DELETE FROM oro_message_queue');
        $connectionMQ->executeQuery('DELETE FROM oro_message_queue_job');
        $connectionMQ->executeQuery('DELETE FROM oro_message_queue_job_unique');

        $this->getFilesystem()
            ->remove(rtrim($this->kernel->getContainer()->getParameter('oro_message_queue.dbal.pid_file_dir')));
    }

    /**
     * {@inheritdoc}
     */
    public function waitWhileProcessingMessages($timeLimit = 600)
    {
        $result = 0;
        while ($result < 3) {
            try {
                if ($this->isQueueEmpty()) {
                    $result++;
                } else {
                    $result = 0;
                }
            } catch (TableNotFoundException $e) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);
                return;
            }

            sleep(1);
            $timeLimit -= 1;
            if ($timeLimit <= 0) {
                throw new RuntimeException('Message Queue was not process messages during time limit');
            }
        }
    }

    /**
     * @return bool
     */
    private function isQueueEmpty()
    {
        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->kernel->getContainer()->get('doctrine');
        /** @var Connection $connection */
        $connection = $doctrine->getManager()->getConnection();
        /** @var Connection $connectionMQ */
        $connectionMQ = $doctrine->getManager('message_queue_job')->getConnection();

        return false == $connection->executeQuery('SELECT * FROM oro_message_queue')->rowCount() +
            $connectionMQ->executeQuery(
                sprintf(
                    "SELECT * FROM oro_message_queue_job WHERE status NOT IN ('%s', '%s')",
                    Job::STATUS_SUCCESS,
                    Job::STATUS_FAILED
                )
            )->rowCount() +
            $connectionMQ->executeQuery('SELECT * FROM oro_message_queue_job_unique')->rowCount();
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
