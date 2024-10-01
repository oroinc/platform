<?php

declare(strict_types=1);

namespace Oro\Bundle\ThemeBundle\Command;

use Oro\Bundle\ThemeBundle\Validator\ConfigurationValidator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The command to validate theme.yml configuration files.
 */
#[AsCommand(
    name: 'oro:theme:configuration:validate',
    description: 'Validates theme.yml configuration files.'
)]
class ThemeValidateConfigurationCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:theme:configuration:validate';

    public function __construct(
        private ConfigurationValidator $validator
    ) {
        parent::__construct();
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command validates theme.yml configuration files.

  <info>php %command.full_name%</info>

HELP
            );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        $messages = $this->validator->validate();
        if ($messages) {
            $symfonyStyle->caution('Found the next errors in "theme.yml" files:');
            $symfonyStyle->listing(array_map(static fn ($message) => "<comment>$message</comment>", $messages));

            return self::FAILURE;
        }

        $symfonyStyle->success('Validation of configuration files "theme.yml" was successful.');

        return self::SUCCESS;
    }
}
