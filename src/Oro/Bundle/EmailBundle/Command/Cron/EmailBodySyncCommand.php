<?php

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
 * The CLI command to synchronize email body.
 */
class EmailBodySyncCommand extends Command implements CronCommandInterface
{
    /**
     * Number of emails in batch
     */
    const BATCH_SIZE = 25;

    /**
     * The maximum execution time (in minutes)
     */
    const MAX_EXEC_TIME_IN_MIN = 15;

    /** @var string */
    protected static $defaultName = 'oro:cron:email-body-sync';

    /** @var FeatureChecker */
    protected $featureChecker;

    /** @var EmailBodySynchronizer */
    protected $synchronizer;

    /**
     * @param FeatureChecker $featureChecker
     * @param EmailBodySynchronizer $synchronizer
     */
    public function __construct(FeatureChecker $featureChecker, EmailBodySynchronizer $synchronizer)
    {
        parent::__construct();

        $this->featureChecker = $featureChecker;
        $this->synchronizer = $synchronizer;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Synchronize email body')
            ->addOption(
                'max-exec-time',
                null,
                InputOption::VALUE_OPTIONAL,
                'The maximum execution time (in minutes). -1 for unlimited. The default value is 15.',
                self::MAX_EXEC_TIME_IN_MIN
            )
            ->addOption(
                'batch-size',
                null,
                InputOption::VALUE_OPTIONAL,
                'Number of emails in batch. The default value is 25.',
                self::BATCH_SIZE
            );
    }

    /**
     * {@inheritdoc}
     */
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
