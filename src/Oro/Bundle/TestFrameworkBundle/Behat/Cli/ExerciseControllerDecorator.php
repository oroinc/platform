<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Command;
use Behat\Testwork\Cli\Controller;
use Behat\Testwork\Tester\Cli\ExerciseController;
use Oro\Bundle\TestFrameworkBundle\Behat\Exception\SkippTestExecutionException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Exercise behat test controller decorator.
 */
class ExerciseControllerDecorator implements Controller
{
    public function __construct(private ExerciseController $exerciseController)
    {
    }

    public function configure(SymfonyCommand $command): void
    {
        $this->exerciseController->configure($command);
    }

    public function execute(InputInterface $input, OutputInterface $output): ?int
    {
        try {
            $result = $this->exerciseController->execute($input, $output);
        } catch (SkippTestExecutionException $exception) {
            return $exception->getCode() !== Command::FAILURE ? Command::SUCCESS : Command::FAILURE;
        }

        return $result;
    }
}
