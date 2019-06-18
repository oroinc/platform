<?php

namespace Oro\Bundle\ImapBundle\Command\Cron;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\EmailBundle\Sync\EmailSynchronizerInterface;
use Oro\Bundle\EmailBundle\Sync\Model\SynchronizationProcessorSettings;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Log\OutputLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Synchronization emails via IMAP
 */
class EmailSyncCommand extends Command implements CronCommandInterface
{
    /**
     * The maximum number of email origins which can be synchronized
     */
    const MAX_TASKS = -1;

    /**
     * The maximum number of synchronization tasks running in the same time
     */
    const MAX_CONCURRENT_TASKS = 5;

    /**
     * The minimum time interval (in minutes) between two synchronizations of the same email origin
     */
    const MIN_EXEC_INTERVAL_IN_MIN = 0;

    /**
     * The maximum execution time (in minutes)
     */
    const MAX_EXEC_TIME_IN_MIN = 15;

    /**
     * The maximum number of jobs running in the same time
     */
    const MAX_JOBS_COUNT = 3;

    /** @var string */
    protected static $defaultName = 'oro:cron:imap-sync';

    /** @var EmailSynchronizerInterface */
    private $imapEmailSynchronizer;

    /** @var FeatureChecker */
    protected $featureChecker;

    /**
     * @param FeatureChecker $featureChecker
     * @param EmailSynchronizerInterface $imapEmailSynchronizer
     */
    public function __construct(
        FeatureChecker $featureChecker,
        EmailSynchronizerInterface $imapEmailSynchronizer
    ) {
        $this->featureChecker = $featureChecker;
        $this->imapEmailSynchronizer = $imapEmailSynchronizer;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public function getDefaultDefinition()
    {
        return '*/1 * * * *';
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->featureChecker->isResourceEnabled(self::$defaultName, 'cron_jobs');
    }

    /**
     * {@internaldoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Synchronization emails via IMAP')
            ->addOption(
                'max-concurrent-tasks',
                null,
                InputOption::VALUE_OPTIONAL,
                'The maximum number of synchronization tasks running in the same time.',
                self::MAX_CONCURRENT_TASKS
            )
            ->addOption(
                'min-exec-interval',
                null,
                InputOption::VALUE_OPTIONAL,
                'The minimum time interval (in minutes) between two synchronizations of the same email origin.',
                self::MIN_EXEC_INTERVAL_IN_MIN
            )
            ->addOption(
                'max-exec-time',
                null,
                InputOption::VALUE_OPTIONAL,
                'The maximum execution time (in minutes). -1 for unlimited.',
                self::MAX_EXEC_TIME_IN_MIN
            )
            ->addOption(
                'max-tasks',
                null,
                InputOption::VALUE_OPTIONAL,
                'The maximum number of email origins which can be synchronized. -1 for unlimited.',
                self::MAX_TASKS
            )
            ->addOption(
                'id',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'The identifier of email origin to be synchronized.'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Allows set the force mode. In this mode all emails will be re-synced again for checked folders. 
                Option "--force" can be used only with option "--id".'
            )
            ->addOption(
                'vvv',
                null,
                InputOption::VALUE_NONE,
                'This option allows show the log messages during resync email'
            );
    }

    /**
     * {@internaldoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->featureChecker->isFeatureEnabled('email')) {
            $output->writeln('The email feature is disabled. The command will not run.');

            return 0;
        }

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
    }

    /**
     * {@internaldoc}
     */
    public function getMaxJobsCount()
    {
        return self::MAX_JOBS_COUNT;
    }

    /**
     * @param OutputInterface $output
     */
    protected function writeAttentionMessageForOptionForce(OutputInterface $output)
    {
        $output->writeln(
            '<comment>ATTENTION</comment>: The option "force" can be used only for concrete email origins.'
        );
        $output->writeln(
            '           So you should add option "id" with required value of email origin in command line.'
        );
    }
}
