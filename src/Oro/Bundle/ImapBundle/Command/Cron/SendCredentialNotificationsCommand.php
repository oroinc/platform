<?php

declare(strict_types=1);

namespace Oro\Bundle\ImapBundle\Command\Cron;

use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\SyncCredentialsIssueManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sends notifications if email origin sync failed due to invalid credentials.
 */
#[AsCommand(
    name: 'oro:cron:imap-credential-notifications',
    description: 'Sends notifications if email origin sync failed due to invalid credentials.'
)]
class SendCredentialNotificationsCommand extends Command implements CronCommandScheduleDefinitionInterface
{
    private SyncCredentialsIssueManager $syncCredentialsIssueManager;

    public function __construct(SyncCredentialsIssueManager $syncCredentialsIssueManager)
    {
        parent::__construct();
        $this->syncCredentialsIssueManager = $syncCredentialsIssueManager;
    }

    #[\Override]
    public function getDefaultDefinition(): string
    {
        return '0 4 * * *';
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command sends notifications
if email origin sync failed due to invalid credentials.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
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

        return Command::SUCCESS;
    }
}
