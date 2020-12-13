<?php
declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Command\Cron;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\EmailBundle\Sync\EmailBodySynchronizer;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Log\OutputLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\SemaphoreStore;

/**
 * Synchronizes email bodies.
 */
class EmailBodySyncCommand extends Command implements CronCommandInterface
{
    /** Number of emails in batch */
    public const BATCH_SIZE = 25;

    /** The maximum execution time (in minutes) */
    public const MAX_EXEC_TIME_IN_MIN = 15;

    /** @var string */
    protected static $defaultName = 'oro:cron:email-body-sync';

    protected FeatureChecker $featureChecker;
    protected EmailBodySynchronizer $synchronizer;

    public function __construct(FeatureChecker $featureChecker, EmailBodySynchronizer $synchronizer)
    {
        parent::__construct();

        $this->featureChecker = $featureChecker;
        $this->synchronizer = $synchronizer;
    }

    public function getDefaultDefinition()
    {
        return '*/30 * * * *';
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->featureChecker->isResourceEnabled(self::getDefaultName(), 'cron_jobs');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption(
                'max-exec-time',
                null,
                InputOption::VALUE_OPTIONAL,
                'Maximum execution time in minutes (-1 for unlimited)',
                self::MAX_EXEC_TIME_IN_MIN
            )
            ->addOption(
                'batch-size',
                null,
                InputOption::VALUE_OPTIONAL,
                'Number of emails to process in a single batch',
                self::BATCH_SIZE
            )
            ->setDescription('Synchronizes email bodies.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command synchronizes email bodies.

  <info>php %command.full_name%</info>

The <info>--max-exec-time</info> option can be used to override the default execution timeout (use -1 for unlimited):

  <info>php %command.full_name% --max-exec-time=<minutes></info>
  <info>php %command.full_name% --max-exec-time=-1</info>

Use <info>--batch-size</info> option to specify the number of email in a single processing batch:

  <info>php %command.full_name% --batch-size=<number></info>

HELP
            )
            ->addUsage('--max-exec-time=<minutes>')
            ->addUsage('--max-exec-time=-1')
            ->addUsage('--batch-size=<number>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->featureChecker->isFeatureEnabled('email')) {
            $output->writeln('The email feature is disabled. The command will not run.');

            return 0;
        }

        $store = new SemaphoreStore();
        $lockFactory = new Factory($store);

        $lock = $lockFactory->createLock('oro:cron:email-body-sync');
        if (!$lock->acquire()) {
            $output->writeln('The command is already running in another process.');

            return 0;
        }

        $this->synchronizer->setLogger(new OutputLogger($output));
        $this->synchronizer->sync((int)$input->getOption('max-exec-time'), (int)$input->getOption('batch-size'));

        $lock->release();

        return 0;
    }
}
