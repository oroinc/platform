<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Pauses test execution after each step in interactive mode.
 *
 * This event subscriber listens for step completion events and prompts the user to press
 * RETURN to continue, allowing developers to inspect the test state between steps.
 */
class InteractiveExecutionSubscriber implements EventSubscriberInterface
{
    /**
     * @var OutputInterface
     */
    protected $output;

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            AfterStepTested::AFTER  => ['afterStep', 1000],
        ];
    }

    public function afterStep(AfterStepTested $event)
    {
        if (false === $event->getTestResult()->isPassed()) {
            return;
        }

        $this->output->writeln('Press [RETURN] to continue...');
        while (fgets(STDIN, 1024) == '') {
        }
    }
}
