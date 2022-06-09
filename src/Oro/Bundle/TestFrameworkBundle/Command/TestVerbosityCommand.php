<?php

namespace Oro\Bundle\TestFrameworkBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command is used in functional tests to check that an expected verbosity level is set for a command.
 * We want to test it in functional tests because Symfony has a quite unclear behaviour regarding the verbosity level,
 * because it is stored in different places: property of Output class, env variable, $_ENV and $_SERVER.
 * @link https://github.com/symfony/symfony/pull/24425
 * @see  \Symfony\Component\Console\Application::configureIO
 */
class TestVerbosityCommand extends Command
{
    protected static $defaultName = 'oro:test:verbosity';

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Test verbosity level.');

        $verbosity = $this->getVerbosity($output);
        if (null !== $verbosity) {
            $output->writeln('Verbosity: ' . $verbosity);
        }

        return 0;
    }

    private function getVerbosity(OutputInterface $output): ?string
    {
        if (OutputInterface::VERBOSITY_NORMAL === $output->getVerbosity()) {
            return 'Normal';
        }
        if (OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity()) {
            return 'Verbose';
        }
        if (OutputInterface::VERBOSITY_VERY_VERBOSE === $output->getVerbosity()) {
            return 'VeryVerbose';
        }
        if (OutputInterface::VERBOSITY_DEBUG === $output->getVerbosity()) {
            return 'Debug';
        }

        return null;
    }
}
