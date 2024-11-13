<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Oro\Bundle\TestFrameworkBundle\Behat\Session\Mink\WatchModeSessionHolder;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Watch mode allows the test to continue running after a fix without the need for a full restart.
 */
readonly class WatchModeController implements Controller
{
    public function __construct(private readonly WatchModeSessionHolder $sessionHolder)
    {
    }

    public function configure(SymfonyCommand $command): void
    {
        $command->addOption(
            '--watch',
            null,
            InputOption::VALUE_OPTIONAL,
            'Watch mode allows the test to continue running after a fix without requiring a complete restart',
            null
        );
        $command->addOption(
            '--watch-from',
            null,
            InputOption::VALUE_OPTIONAL,
            'Staring test from the passed line after the test step is fall with enabled --watch mode',
            null // start test from the failed step or previous passed step line
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        if ($input->hasParameterOption(['--watch'])) {
            $this->sessionHolder->setIsWatch(true);
        }
        if (is_numeric($input->getOption('watch-from'))) {
            $this->sessionHolder->setWatchFrom((int)$input->getOption('watch-from'));
        }
        $additionalOptions = [];
        if (!empty($input->getOption('verbose'))) {
            $additionalOptions[] = sprintf('-%s', $input->getOption('verbose'));
        }
        $this->sessionHolder->setAdditionalOptions($additionalOptions);
    }
}
