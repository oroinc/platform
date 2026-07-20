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
 * Displays all Twig functions registered for the email template sandbox.
 */
#[AsCommand(
    name: 'oro:debug:email:functions',
    description: 'Displays all Twig functions registered for the email template sandbox.'
)]
class DebugEmailFunctionsCommand extends Command
{
    public function __construct(
        private readonly EmailTemplateSecurityPolicy $emailTemplateSecurityPolicy,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setHelp(
            <<<'HELP'
The <info>%command.name%</info> command lists all Twig functions allowed in email templates.
HELP
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $functions = $this->emailTemplateSecurityPolicy->getFunctions();

        $io->section('Allowed Functions');

        if (empty($functions)) {
            $io->text('N/A');

            return Command::SUCCESS;
        }

        sort($functions);

        $io->listing($functions);

        return Command::SUCCESS;
    }
}
