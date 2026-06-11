<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\SecurityPolicyInspector\EmailTemplateSecurityPolicyInspectionResult;
use Oro\Bundle\EmailBundle\SecurityPolicyInspector\EmailTemplateSecurityPolicyInspector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Checks email templates stored in the database for Twig sandbox security policy violations
 * and outputs the findings as a flat table.
 */
#[AsCommand(
    name: 'oro:email:template:security-policy-check',
    description: 'Checks email templates for Twig sandbox security policy violations.'
)]
class EmailTemplateSecurityPolicyCheckCommand extends Command
{
    public function __construct(
        private readonly EmailTemplateSecurityPolicyInspector $emailTemplateSecurityPolicyInspector,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        // phpcs:disable
        $this
            ->addArgument(
                'template',
                InputArgument::OPTIONAL,
                'The name of the email template to check. If omitted, all templates in the database are checked.'
            )
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command checks email templates for Twig sandbox
security policy violations and outputs a table of every finding.

  <comment>Security policy violations are detected via static analysis only.</comment>
  Some violations may only manifest at runtime, and false positives
  or false negatives are possible.

To check a specific email template by name:

  <info>php %command.full_name% <template-name></info>

The <comment><template-name></comment> can be specified in two formats:

1. Plain name:

   <info>php %command.full_name% user_reset_password</info>
   <info>php %command.full_name% order_confirmation</info>
   <info>php %command.full_name% rfq_created_confirmation</info>

2. Fully-qualified name (with optional entity name and/or context parameters):

   <info>php %command.full_name% @db:entityName=Oro\Bundle\UserBundle\Entity\User/user_reset_password</info>
   <info>php %command.full_name% @db:entityName=Oro\Bundle\UserBundle\Entity\User&localization=42/user_reset_password</info>

Both formats are accepted and produce the same result.

To check all email templates stored in the database:

  <info>php %command.full_name%</info>

The command always exits with code 0 when the check completes, even when
violations are found. It exits with code 1 only when the specified template
name does not exist in the database.
HELP
            );
        // phpcs:enable
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $templateName = $input->getArgument('template');

        if ($templateName !== null) {
            return $this->checkSingleTemplate($templateName, $io);
        }

        return $this->checkAllTemplates($io);
    }

    private function checkSingleTemplate(string $name, SymfonyStyle $io): int
    {
        $result = $this->emailTemplateSecurityPolicyInspector->inspectByName($name);

        if ($result === null) {
            $io->error(sprintf('Email template "%s" not found.', $name));

            return Command::FAILURE;
        }

        if (!$result->hasViolations()) {
            $io->success(sprintf('No security policy violations found in template "%s".', $name));

            return Command::SUCCESS;
        }

        $this->renderViolationsTable($io, [$result]);

        return Command::SUCCESS;
    }

    private function checkAllTemplates(SymfonyStyle $io): int
    {
        $results = $this->emailTemplateSecurityPolicyInspector->inspectAll();
        $violatingResults = array_values(
            array_filter(
                $results,
                static fn (EmailTemplateSecurityPolicyInspectionResult $r) => $r->hasViolations()
            )
        );

        if (count($violatingResults) === 0) {
            $io->success('No security policy violations found in any email template.');

            return Command::SUCCESS;
        }

        $this->renderViolationsTable($io, $violatingResults);

        $io->note(sprintf(
            '%d of %d template(s) have security policy violations.',
            count($violatingResults),
            count($results)
        ));

        return Command::SUCCESS;
    }

    /**
     * @param list<EmailTemplateSecurityPolicyInspectionResult> $results
     */
    private function renderViolationsTable(SymfonyStyle $io, array $results): void
    {
        $rows = [];
        foreach ($results as $result) {
            foreach ($result->getViolations() as $violation) {
                $emailTemplate = $result->getEmailTemplate();

                $rows[] = [
                    $emailTemplate->getName(),
                    $emailTemplate->getEntityName() ?? '',
                    $violation->getMessage(),
                ];
            }
        }

        $io->table(['Template', 'Entity', 'Violation'], $rows);
    }
}
