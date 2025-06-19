<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatJunitExtension\Output\Printer;

use Behat\Behat\Hook\Scope\StepScope;
use Behat\Behat\Output\Node\Printer\SetupPrinter;
use Behat\Testwork\Call\CallResults;
use Behat\Testwork\Exception\ExceptionPresenter;
use Behat\Testwork\Hook\Tester\Setup\HookedSetup;
use Behat\Testwork\Hook\Tester\Setup\HookedTeardown;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Tester\Setup\Setup;
use Behat\Testwork\Tester\Setup\Teardown;

/**
 * A copy of {@see \Behat\Behat\Output\Node\Printer\JUnit\JUnitSetupPrinter}
 * that replaces assertion for JUnitOutputPrinter.
 */
class JUnitSetupPrinter implements SetupPrinter
{
    public function __construct(private ExceptionPresenter $exceptionPresenter)
    {
    }

    #[\Override]
    public function printSetup(Formatter $formatter, Setup $setup)
    {
        if (!$setup->isSuccessful()) {
            if ($setup instanceof HookedSetup) {
                $this->handleHookCalls($formatter, $setup->getHookCallResults(), 'setup');
            }
        }
    }

    #[\Override]
    public function printTeardown(Formatter $formatter, Teardown $teardown)
    {
        if (!$teardown->isSuccessful()) {
            if ($teardown instanceof HookedTeardown) {
                $this->handleHookCalls($formatter, $teardown->getHookCallResults(), 'teardown');
            }
        }
    }

    private function handleHookCalls(Formatter $formatter, CallResults $results, string $messageType): void
    {
        foreach ($results as $hookCallResult) {
            if ($hookCallResult->hasException()) {
                $call = $hookCallResult->getCall();
                $scope = $call->getScope();
                $outputPrinter = $formatter->getOutputPrinter();
                /** customization start */
                assert($outputPrinter instanceof JUnitOutputPrinter);
                /** customization end */

                $callee = $call->getCallee();
                $message = $callee->getName();
                if ($scope instanceof StepScope) {
                    $message .= ': ' . $scope->getStep()->getKeyword() . ' ' . $scope->getStep()->getText();
                }
                $message .= ': ' . $this->exceptionPresenter->presentException($hookCallResult->getException());

                $attributes = array(
                    'message' => $message,
                    'type' => $messageType,
                );

                $outputPrinter->addTestcaseChild('failure', $attributes);
            }
        }
    }
}
