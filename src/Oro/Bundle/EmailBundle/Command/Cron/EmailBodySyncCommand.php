<?php

namespace Oro\Bundle\EmailBundle\Command\Cron;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\EmailBundle\Sync\EmailBodySynchronizer;
use Oro\Component\Log\OutputLogger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;

class EmailBodySyncCommand extends ContainerAwareCommand implements CronCommandInterface
{
    /**
     * Command name
     */
    const COMMAND_NAME = 'oro:cron:email-body-sync';

    /**
     * Number of emails in batch
     */
    const BATCH_SIZE = 25;

    /**
     * The maximum execution time (in minutes)
     */
    const MAX_EXEC_TIME_IN_MIN = 15;

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
        $featureChecker = $this->getContainer()->get('oro_featuretoggle.checker.feature_checker');

        return $featureChecker->isResourceEnabled(self::COMMAND_NAME, 'cron_jobs');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
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
        $featureChecker = $this->getContainer()->get('oro_featuretoggle.checker.feature_checker');
        if (!$featureChecker->isFeatureEnabled('email')) {
            $output->writeln('The email feature is disabled. The command will not run.');

            return 0;
        }

        $lock = new LockHandler('oro:cron:email-body-sync');
        if (!$lock->lock()) {
            $output->writeln('The command is already running in another process.');

            return 0;
        }
        /** @var EmailBodySynchronizer $synchronizer */
        $synchronizer = $this->getContainer()->get('oro_email.email_body_synchronizer');
        $synchronizer->setLogger(new OutputLogger($output));
        $synchronizer->sync((int)$input->getOption('max-exec-time'), (int)$input->getOption('batch-size'));

        $lock->release();

        return 0;
    }
}
