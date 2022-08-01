<?php
declare(strict_types=1);

namespace Oro\Bundle\ImapBundle\Command\Cron;

use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\EmailBundle\Sync\EmailSynchronizerInterface;
use Oro\Bundle\EmailBundle\Sync\Model\SynchronizationProcessorSettings;
use Oro\Component\Log\OutputLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Synchronizes emails via IMAP.
 */
class EmailSyncCommand extends Command implements CronCommandScheduleDefinitionInterface
{
    /** The maximum number of email origins which can be synchronized */
    public const MAX_TASKS = -1;

    /** The maximum number of synchronization tasks running in the same time */
    public const MAX_CONCURRENT_TASKS = 5;

    /** The minimum time interval (in minutes) between two synchronizations of the same email origin */
    public const MIN_EXEC_INTERVAL_IN_MIN = 0;

    /** The maximum execution time (in minutes) */
    public const MAX_EXEC_TIME_IN_MIN = 15;

    /** The maximum number of jobs running in the same time */
    public const MAX_JOBS_COUNT = 3;

    /** @var string */
    protected static $defaultName = 'oro:cron:imap-sync';

    private EmailSynchronizerInterface $imapEmailSynchronizer;

    public function __construct(EmailSynchronizerInterface $imapEmailSynchronizer)
    {
        $this->imapEmailSynchronizer = $imapEmailSynchronizer;
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition(): string
    {
        return '*/1 * * * *';
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function configure()
    {
        $this
            ->addOption(
                'max-concurrent-tasks',
                null,
                InputOption::VALUE_OPTIONAL,
                'Maximum number of synchronization tasks running simultaneously',
                self::MAX_CONCURRENT_TASKS
            )
            ->addOption(
                'min-exec-interval',
                null,
                InputOption::VALUE_OPTIONAL,
                'Minimum time interval (in minutes) between two synchronizations of the same email origin',
                self::MIN_EXEC_INTERVAL_IN_MIN
            )
            ->addOption(
                'max-exec-time',
                null,
                InputOption::VALUE_OPTIONAL,
                'Maximum execution time (in minutes), use -1 for unlimited',
                self::MAX_EXEC_TIME_IN_MIN
            )
            ->addOption(
                'max-tasks',
                null,
                InputOption::VALUE_OPTIONAL,
                'Maximum number of email origins to synchronize (use -1 for unlimited)',
                self::MAX_TASKS
            )
            ->addOption(
                'id',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Identifier of the email origin to be synchronized'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Re-synchronize all emails for checked folders (can be used only with "--id")'
            )
            ->addOption(
                'vvv',
                null,
                InputOption::VALUE_NONE,
                'Display the log messages during email synchronization'
            )
            ->setDescription('Synchronizes emails via IMAP.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command synchronizes emails via IMAP.

  <info>php %command.full_name%</info>

The <info>--max-concurrent-tasks</info> option allows to limit the maximum number
of synchronization tasks running simultaneously:

  <info>php %command.full_name% --max-concurrent-tasks=<number></info>

The <info>--min-exec-interval</info> option specifies the minimum time interval
(in minutes) between two synchronizations of the same email origin:

  <info>php %command.full_name% --min-exec-interval=<minutes></info>

The <info>--max-exec-time</info> option defines the maximum execution time in minutes
(use -1 to remove the limit):

  <info>php %command.full_name% --max-exec-time=<minutes></info>
  <info>php %command.full_name% --max-exec-time=-1</info>

The <info>--max-tasks</info> option limits the maximum number of email origins
to synchronize (use -1 for unlimited):

  <info>php %command.full_name% --max-tasks=<number></info>
  <info>php %command.full_name% --max-tasks=-1</info>

The <info>--id</info> option can be used to provide the identifiers
of the email origins to be synchronized:

  <info>php %command.full_name% --id=<ID1> --id=<ID2> --id=<IDN></info>

The <info>--force</info> option can be used to re-synchronize all emails
for checked folders (requires <info>--id</info>):

  <info>php %command.full_name% --force --id=<ID></info>

The <info>--vvv</info> option enables additional logging during email synchronization:

  <info>php %command.full_name% --force --id=<ID> --vvv</info>

HELP
            )
            ->addUsage('--max-concurrent-tasks=<number>')
            ->addUsage('--min-exec-interval=<minutes>')
            ->addUsage('--max-exec-time=<minutes>')
            ->addUsage('--max-exec-time=-1')
            ->addUsage('--max-tasks=<number>')
            ->addUsage('--max-tasks=-1')
            ->addUsage('--id=<ID1> --id=<ID2> --id=<IDN>')
            ->addUsage('--force --id=<ID>')
            ->addUsage('--force --id=<ID> --vvv')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->imapEmailSynchronizer->setLogger(new OutputLogger($output));

        $force = $input->getOption('force');
        $showMessage = $input->getOption('vvv');
        $originIds = $input->getOption('id');

        if ($force && empty($originIds)) {
            $this->writeAttentionMessageForOptionForce($output);
        } else {
            if (!empty($originIds)) {
                $settings = new SynchronizationProcessorSettings($force, $showMessage);
                $this->imapEmailSynchronizer->syncOrigins($originIds, $settings);
            } else {
                $this->imapEmailSynchronizer->sync(
                    (int)$input->getOption('max-concurrent-tasks'),
                    (int)$input->getOption('min-exec-interval'),
                    (int)$input->getOption('max-exec-time'),
                    (int)$input->getOption('max-tasks')
                );
            }
        }

        return 0;
    }

    public function getMaxJobsCount()
    {
        return self::MAX_JOBS_COUNT;
    }

    protected function writeAttentionMessageForOptionForce(OutputInterface $output): void
    {
        $output->writeln(
            '<comment>ATTENTION</comment>: The option "force" can be used only for concrete email origins.'
        );
        $output->writeln(
            '           So you should add option "id" with required value of email origin in command line.'
        );
    }
}
