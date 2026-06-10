<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\Twig\EmailTemplateSecurityPolicy;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Displays all Twig filters registered for the email template sandbox.
 */
#[AsCommand(
    name: 'oro:debug:email:filters',
    description: 'Displays all Twig filters registered for the email template sandbox.'
)]
class DebugEmailFiltersCommand extends Command
{
    public function __construct(
        private readonly EmailTemplateSecurityPolicy $emailSandboxAllowlist,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setHelp(
            <<<'HELP'
The <info>%command.name%</info> command lists all Twig filters allowed in email templates.
HELP
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $filters = $this->emailSandboxAllowlist->getFilters();

        $io->section('Allowed Filters');

        if (empty($filters)) {
            $io->text('N/A');

            return Command::SUCCESS;
        }

        sort($filters);

        $io->listing($filters);

        return Command::SUCCESS;
    }
}
