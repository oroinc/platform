<?php

namespace Oro\Bundle\CronBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\CronBundle\Command\Logger\OutputLogger;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

class CleanupCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const COMMAND_NAME = 'oro:cron:cleanup';
    const BATCH_SIZE   = 200;
    const DAYS         = 1; // cleanup entries older than ...

    const EXITCODE_SUCCESS = 0;
    const EXITCODE_FAILED = 1;

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '*/5 * * * *'; // every 5 minutes
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'If option exists items won\'t be deleted, items count that match cleanup criteria will be shown'
            )
            ->setDescription('Clear cron-related log-alike tables: queue, etc');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new OutputLogger($output);
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $con    = $em->getConnection();

        if ($input->getOption('dry-run')) {
            $stm = $this->getConditionStatement($con, true);
            $stm->execute();
            $result = $stm->fetchColumn();

            $message = 'Will be removed %d rows';
        } else {
            $em->beginTransaction();
            $result = 0;
            try {
                $stm = $this->getConditionStatement($con);
                $stm->execute();

                $buf = [];
                while ($id = $stm->fetchColumn()) {
                    $buf[] = $id;
                    $result++;

                    $buf = $this->processBuff($em, $buf);
                }

                $this->processBuff($em, $buf);

                $em->commit();
            } catch (\Exception $e) {
                $em->rollback();
                $logger->critical($e->getMessage(), ['exception' => $e]);

                return self::EXITCODE_FAILED;
            }

            $message = 'Removed %d rows';
        }

        $logger->notice(sprintf($message, $result));
        $logger->notice('Completed');

        return self::EXITCODE_SUCCESS;
    }

    /**
     * @param EntityManager $em
     * @param array         $buf
     * @param int           $size
     *
     * @return array
     */
    protected function processBuff(EntityManager $em, $buf, $size = self::BATCH_SIZE)
    {
        if (count($buf) > $size) {
            $this->flushBatch($em->getConnection(), $buf);

            $em->commit();
            $em->beginTransaction();
            $buf = [];
        }

        return $buf;
    }

    /**
     * Get statement with bound values
     *
     * @param Connection $connection
     * @param bool       $isCount
     *
     * @return Statement
     */
    protected function getConditionStatement(Connection $connection, $isCount = false)
    {
        $sql = "SELECT %s FROM jms_jobs j
                 LEFT JOIN jms_job_dependencies d ON d.source_job_id=j.id
                 WHERE j.closedAt < ? AND j.state NOT IN (?, ?)
                 AND d.dest_job_id IS NULL";
        $sql = sprintf($sql, $isCount ? 'COUNT(j.id)' : 'j.id');

        $date = new \DateTime(sprintf('%d days ago', self::DAYS), new \DateTimeZone('UTC'));
        $date = $date->format('Y-m-d H:i:s');

        $stm = $connection->prepare($sql);
        $stm->bindValue(1, $date);
        $stm->bindValue(2, Job::STATE_RUNNING, \PDO::PARAM_INT);
        $stm->bindValue(3, Job::STATE_PENDING, \PDO::PARAM_INT);

        return $stm;
    }

    /**
     * Flush batch
     *
     * @param Connection $con
     * @param array      $ids
     */
    protected function flushBatch(Connection $con, array $ids)
    {
        $con->executeUpdate(
            "DELETE FROM jms_job_statistics WHERE job_id IN (?)",
            [$ids],
            [Connection::PARAM_INT_ARRAY]
        );

        $con->executeUpdate(
            "DELETE FROM jms_job_dependencies WHERE dest_job_id IN (?)",
            [$ids],
            [Connection::PARAM_INT_ARRAY]
        );

        $con->executeUpdate(
            "DELETE FROM jms_job_related_entities WHERE job_id IN (?)",
            [$ids],
            [Connection::PARAM_INT_ARRAY]
        );

        $con->executeUpdate(
            "DELETE FROM jms_jobs WHERE id IN (?)",
            [$ids],
            [Connection::PARAM_INT_ARRAY]
        );
    }
}
