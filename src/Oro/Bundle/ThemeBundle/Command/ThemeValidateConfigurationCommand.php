<?php

declare(strict_types=1);

namespace Oro\Bundle\ThemeBundle\Command;

use Oro\Bundle\ThemeBundle\Validator\ChainConfigurationValidator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command validates theme configuration files theme.yml
 */
class ThemeValidateConfigurationCommand extends Command
{
    public function __construct(
        private ChainConfigurationValidator $validator
    ) {
        parent::__construct();
    }

    /**
     * @var string
     */
    protected static $defaultName = 'oro:theme:configuration:validate';

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function configure()
    {
        $this
            ->setDescription('Command validates theme configuration files theme.yml.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command validates theme configuration files theme.yml.

  <info>php %command.full_name%</info>

HELP
            );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection                                  PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $messages = $this->validator->validate();

        if (count($messages)) {
            $this->showValidationMessages($symfonyStyle, $messages);
            return self::FAILURE;
        }

        $symfonyStyle->success('Validation of configuration files "theme.yml" was successful.');

        return self::SUCCESS;
    }

    private function showValidationMessages(SymfonyStyle $symfonyStyle, array $messages): void
    {
        $symfonyStyle->caution('Found the next errors in "theme.yml" files:');

        $messages = array_map(static fn ($message) => "<comment>$message</comment>", $messages);
        $symfonyStyle->listing($messages);
    }
}
