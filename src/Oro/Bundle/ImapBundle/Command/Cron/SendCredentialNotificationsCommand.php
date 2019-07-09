<?php

namespace Oro\Bundle\ImapBundle\Command\Cron;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\SyncCredentialsIssueManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cron command that runs processing the invalid email origins that was failed during sync.
 */
class SendCredentialNotificationsCommand extends Command implements CronCommandInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:imap-credential-notifications';

    /** @var FeatureChecker */
    private $featureChecker;

    /** @var SyncCredentialsIssueManager */
    private $syncCredentialsIssueManager;

    /**
     * @param FeatureChecker $featureChecker
     * @param SyncCredentialsIssueManager $syncCredentialsIssueManager
     */
    public function __construct(
        FeatureChecker $featureChecker,
        SyncCredentialsIssueManager $syncCredentialsIssueManager
    ) {
        parent::__construct();

        $this->featureChecker = $featureChecker;
        $this->syncCredentialsIssueManager = $syncCredentialsIssueManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '0 4 * * *';
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
            ->setDescription('Send wrong email credentials notifications');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Process the invalid credentials origins</info>');
        $processedOrigins = $this->syncCredentialsIssueManager->processInvalidOrigins();
        if (count($processedOrigins)) {
            $output->writeln('<info>Processed origins:</info>', OutputInterface::VERBOSITY_DEBUG);
            foreach ($processedOrigins as $processedOrigin) {
                $output->writeln(
                    sprintf(
                        '<comment>id: %s, username: %s, host: %s</comment>',
                        $processedOrigin->getId(),
                        $processedOrigin->getUser(),
                        $processedOrigin->getImapHost()
                    ),
                    OutputInterface::VERBOSITY_DEBUG
                );
            }
        } else {
            $output->writeln(
                '<info>Invalid credentials origins was not found</info>',
                OutputInterface::VERBOSITY_DEBUG
            );
        }

        return 0;
    }
}
