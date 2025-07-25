<?php

declare(strict_types=1);

namespace Oro\Bundle\BatchBundle\Command;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Job\BatchStatus;
use Oro\Bundle\BatchBundle\Job\DoctrineJobRepository;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\CronBundle\Command\CronCommandActivationInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Deletes old batch job records.
 */
#[AsCommand(
    name: 'oro:cron:batch:cleanup',
    description: 'Deletes old batch job records.'
)]
class CleanupCommand extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandActivationInterface
{
    public const FLUSH_BATCH_SIZE = 100;

    private DoctrineJobRepository $doctrineJobRepository;
    private string $batchCleanupInterval;

    public function __construct(DoctrineJobRepository $doctrineJobRepository, string $batchCleanupInterval)
    {
        $this->doctrineJobRepository = $doctrineJobRepository;
        $this->batchCleanupInterval = $batchCleanupInterval;
        parent::__construct();
    }

    #[\Override]
    public function getDefaultDefinition(): string
    {
        return '0 1 * * *';
    }

    #[\Override]
    public function isActive(): bool
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
    #[\Override]
    protected function configure()
    {
        $this
            ->addOption(
                'interval',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Time interval to keep the batch records (e.g. "2 weeks")'
            )
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
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $interval = $input->getOption('interval');

        $jobInstances = $this->getObsoleteJobInstancesQueryBuilder($this->prepareDateInterval($interval));
        $jobInstanceIterator = new BufferedIdentityQueryResultIterator($jobInstances);
        $jobInstanceIterator->setBufferSize(self::FLUSH_BATCH_SIZE);
        $jobInstanceIterator->setHydrationMode(Query::HYDRATE_SCALAR);

        if (!count($jobInstanceIterator)) {
            $output->writeln('<info>There are no jobs eligible for clean up</info>');

            return Command::FAILURE;
        }
        $output->writeln(sprintf('<comment>Batch jobs will be deleted:</comment> %d', count($jobInstanceIterator)));

        $this->deleteRecords($jobInstanceIterator, JobInstance::class);

        $output->writeln('<info>Batch job history cleanup complete</info>');

        return Command::SUCCESS;
    }

    /**
     * Subtract given interval from current date time
     */
    private function prepareDateInterval(?string $intervalString = null): \DateTime
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $intervalString = $intervalString ?: $this->batchCleanupInterval;
        $date->sub(\DateInterval::createFromDateString($intervalString));

        return $date;
    }

    /**
     * Delete records using iterator.
     */
    private function deleteRecords(BufferedIdentityQueryResultIterator $iterator, string $fqcn): void
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

    private function processBatch(array $ids, string $className): void
    {
        $this->doctrineJobRepository->getJobManager()
            ->getRepository($className)
            ->createQueryBuilder('entity')
            ->delete($className, 'entity')
            ->where('entity.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
    }

    /**
     * Finds job instances where job executions created before the given date time.
     */
    private function getObsoleteJobInstancesQueryBuilder(\DateTime $endTime): QueryBuilder
    {
        return $this->doctrineJobRepository->getJobManager()
            ->getRepository(JobInstance::class)
            ->createQueryBuilder('ji')
            ->resetDQLPart('select')
            ->select('ji.id')
            ->leftJoin('ji.jobExecutions', 'je')
            ->where('je.status NOT IN (:statuses)')
            ->andWhere('je.createTime < (:endTime)')
            ->setParameter('statuses', [BatchStatus::STARTING, BatchStatus::STARTED])
            ->setParameter('endTime', $endTime, Types::DATETIME_MUTABLE);
    }
}
