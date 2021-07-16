<?php
declare(strict_types=1);

namespace Oro\Bundle\BatchBundle\Command;

use Akeneo\Bundle\BatchBundle\Job\BatchStatus;
use Akeneo\Bundle\BatchBundle\Job\DoctrineJobRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Deletes old batch job records.
 */
class CleanupCommand extends Command implements CronCommandInterface
{
    public const FLUSH_BATCH_SIZE = 100;

    /** @var string */
    protected static $defaultName = 'oro:cron:batch:cleanup';

    private DoctrineJobRepository $akeneoJobRepository;

    private string $batchCleanupInterval;

    public function __construct(DoctrineJobRepository $akeneoJobRepository, string $batchCleanupInterval)
    {
        $this->akeneoJobRepository = $akeneoJobRepository;
        $this->batchCleanupInterval = $batchCleanupInterval;
        parent::__construct();
    }

    public function getDefaultDefinition()
    {
        return '0 1 * * *';
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->sub(\DateInterval::createFromDateString($this->batchCleanupInterval));
        $qb = $this->getObsoleteJobInstancesQueryBuilder($date);

        $count = $qb->select('COUNT(ji.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return ($count > 0);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption(
                'interval',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Time interval to keep the batch records (e.g. "2 weeks")'
            )
            ->setDescription('Deletes old batch job records.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command deletes old batch job records.

  <info>php %command.full_name%</info>

The <info>--interval</info> option allows to clean only those job records that are older than the provided
time interval. Any notation that can be parsed by <comment>\DateInterval::createFromDateString()</comment>
is accepted (see <comment>https://php.net/manual/dateinterval.createfromdatestring.php</comment>):

  <info>php %command.full_name% --interval=<interval></info>
  <info>php %command.full_name% --interval="2 weeks"</info>

HELP
            )
            ->addUsage('--interval=<interval>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $interval = $input->getOption('interval');

        $jobInstances = $this->getObsoleteJobInstancesQueryBuilder($this->prepareDateInterval($interval));
        $jobInstanceIterator = new BufferedIdentityQueryResultIterator($jobInstances);
        $jobInstanceIterator->setBufferSize(self::FLUSH_BATCH_SIZE);
        $jobInstanceIterator->setHydrationMode(Query::HYDRATE_SCALAR);

        if (!count($jobInstanceIterator)) {
            $output->writeln('<info>There are no jobs eligible for clean up</info>');

            return;
        }
        $output->writeln(sprintf('<comment>Batch jobs will be deleted:</comment> %d', count($jobInstanceIterator)));

        $this->deleteRecords($jobInstanceIterator, 'AkeneoBatchBundle:JobInstance');

        $output->writeln('<info>Batch job history cleanup complete</info>');
    }

    /**
     * Subtract given interval from current date time
     */
    protected function prepareDateInterval(?string $intervalString = null): \DateTime
    {
        $date           = new \DateTime('now', new \DateTimeZone('UTC'));
        $intervalString = $intervalString ?: $this->batchCleanupInterval;
        $date->sub(\DateInterval::createFromDateString($intervalString));

        return $date;
    }

    /**
     * Delete records using iterator
     *
     * @throws \Exception
     */
    protected function deleteRecords(BufferedIdentityQueryResultIterator $iterator, string $fqcn): void
    {
        $iteration = 0;

        $ids = [];
        foreach ($iterator as $row) {
            $ids[] = reset($row);

            $iteration++;
            if ($iteration % self::FLUSH_BATCH_SIZE == 0) {
                $this->processBatch($ids, $fqcn);
            }
        }
        if ($iteration % self::FLUSH_BATCH_SIZE > 0) {
            $this->processBatch($ids, $fqcn);
        }
    }

    protected function processBatch(array $ids, string $className): void
    {
        $this->getEntityManager()
            ->getRepository($className)
            ->createQueryBuilder('entity')
            ->delete($className, 'entity')
            ->where('entity.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
    }

    /**
     * Find job instances where job executions created before the given date time
     *
     * @param $endTime
     *
     * @return QueryBuilder
     */
    protected function getObsoleteJobInstancesQueryBuilder($endTime)
    {
        $repository = $this->getEntityManager()->getRepository('AkeneoBatchBundle:JobInstance');

        return $repository->createQueryBuilder('ji')
            ->resetDQLPart('select')
            ->select('ji.id')
            ->leftJoin('ji.jobExecutions', 'je')
            ->where('je.status NOT IN (:statuses)')
            ->andWhere('je.createTime < (:endTime)')
            ->setParameter(
                'statuses',
                [BatchStatus::STARTING, BatchStatus::STARTED]
            )
            ->setParameter('endTime', $endTime, Types::DATETIME_MUTABLE);
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->akeneoJobRepository->getJobManager();
    }
}
