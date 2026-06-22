<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\Twig\EmailTemplateSecurityPolicy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Displays all Twig tags registered for the email template sandbox.
 */
class DebugEmailTagsCommand extends Command
{
    protected static $defaultName = 'oro:debug:email:tags';
    protected static $defaultDescription = 'Displays all Twig tags registered for the email template sandbox.';

    public function __construct(
        private readonly EmailTemplateSecurityPolicy $emailSandboxAllowlist,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setHelp(
            <<<'HELP'
The <info>%command.name%</info> command lists all Twig tags allowed in email templates.
HELP
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $tags = $this->emailSandboxAllowlist->getTags();

        $io->section('Allowed Tags');

        if (empty($tags)) {
            $io->text('N/A');

            return Command::SUCCESS;
        }

        sort($tags);

        $io->listing($tags);

        return Command::SUCCESS;
    }
}
