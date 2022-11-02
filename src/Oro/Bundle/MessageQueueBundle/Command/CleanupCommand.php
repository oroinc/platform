<?php
declare(strict_types=1);

namespace Oro\Bundle\MessageQueueBundle\Command;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Job\Job as JobComponent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clears old records from message_queue_job table.
 */
class CleanupCommand extends Command implements CronCommandScheduleDefinitionInterface
{
    public const INTERVAL_FOR_SUCCESSES = '-2 weeks';
    public const INTERVAL_FOR_FAILED = '-1 month';

    /** @var string */
    protected static $defaultName = 'oro:cron:message-queue:cleanup';

    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition(): string
    {
        return '0 1 * * *';
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Show the number of jobs that match the cleanup criteria instead of deletion'
            )
            ->setDescription('Clears old records from message_queue_job table.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command clears successful job records
that are older than 2 weeks and failed job records older than 1 month
from <comment>message_queue_job</comment> table.

  <info>php %command.full_name%</info>

The <info>--dry-run</info> option can be used to show the number of jobs that match
the cleanup criteria instead of deleting them:

  <info>php %command.full_name% --dry-run</info>

HELP
            )
            ->addUsage('--dry-run')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('dry-run')) {
            $output->writeln(
                sprintf(
                    '<info>Number of jobs that would be deleted: %d</info>',
                    $this->countRecords()
                )
            );

            return 0;
        }

        $output->writeln(sprintf(
            '<comment>Number of jobs that has been deleted:</comment> %d',
            $this->deleteRecords()
        ));

        $output->writeln('<info>Message queue job history cleanup complete</info>');

        return 0;
    }

    /**
     * @return mixed
     */
    private function deleteRecords()
    {
        $qb = $this->getEntityManager()
            ->getRepository(Job::class)
            ->createQueryBuilder('job');
        $qb->delete(Job::class, 'job');
        $this->addOutdatedJobsCriteria($qb);

        return $qb->getQuery()->execute();
    }

    /**
     * @return mixed
     */
    private function countRecords()
    {
        $qb = $this->getEntityManager()
            ->getRepository(Job::class)
            ->createQueryBuilder('job');
        $qb->select('COUNT(job.id)');
        $this->addOutdatedJobsCriteria($qb);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->doctrineHelper->getEntityManagerForClass(Job::class);
    }

    private function addOutdatedJobsCriteria(QueryBuilder $qb): void
    {
        $qb
            ->where($qb->expr()->andX(
                $qb->expr()->eq('job.status', ':status_success'),
                $qb->expr()->lt('job.stoppedAt', ':success_end_time')
            ))
            ->orWhere($qb->expr()->andX(
                $qb->expr()->eq('job.status', ':status_failed'),
                $qb->expr()->lt('job.stoppedAt', ':failed_end_time')
            ))
            ->setParameter('status_success', JobComponent::STATUS_SUCCESS)
            ->setParameter(
                'success_end_time',
                new \DateTime(self::INTERVAL_FOR_SUCCESSES, new \DateTimeZone('UTC')),
                Types::DATETIME_MUTABLE
            )
            ->setParameter('status_failed', JobComponent::STATUS_FAILED)
            ->setParameter(
                'failed_end_time',
                new \DateTime(self::INTERVAL_FOR_FAILED, new \DateTimeZone('UTC')),
                Types::DATETIME_MUTABLE
            );
    }
}
