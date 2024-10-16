<?php

namespace Oro\Bundle\DistributionBundle\Runtime;

use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Runtime\Runner\Symfony\ConsoleApplicationRunner;
use Symfony\Component\Runtime\RunnerInterface;
use Symfony\Component\Runtime\SymfonyRuntime;

/**
 * Send logs to stderr in containers using monolog and use stdout only for commands.
 *
 * Regular output and user-friendy error messages go to stdout
 * CRITICAL logs go to stderr
 */
class CloudRuntime extends SymfonyRuntime
{
    #[\Override]
    public function getRunner(?object $application): RunnerInterface
    {
        if (ini_get('display_errors') === 'stderr') {
            throw new \RuntimeException('You can not send PHP errors and warnings to stderr in containers.');
        }

        $runner = parent::getRunner($application);
        if ($runner instanceof ConsoleApplicationRunner) {
            /** @var OutputInterface $output */
            $output = \Closure::bind(
                function () {
                    return $this->output;
                },
                $runner,
                $runner
            )();

            if ($output instanceof ConsoleOutputInterface) {
                $errorOutput = $output->getErrorOutput();
                $output->setErrorOutput(
                    new StreamOutput(
                        $output->getStream(),
                        $errorOutput->getVerbosity(),
                        $errorOutput->isDecorated(),
                        $errorOutput->getFormatter()
                    )
                );
            }
        }

        return $runner;
    }
}
