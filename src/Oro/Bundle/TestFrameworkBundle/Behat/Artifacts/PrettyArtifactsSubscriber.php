<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\OutlineTested;
use Behat\Mink\Mink;
use Behat\Testwork\Output\NodeEventListeningFormatter;
use Behat\Testwork\Output\Printer\OutputPrinter;
use Behat\Testwork\Tester\Result\TestResult;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Prints artifacts links on Behat step fail
 */
class PrettyArtifactsSubscriber implements EventSubscriberInterface
{
    protected bool $isOutline = false;
    protected OutputPrinter $printer;
    protected ScreenshotGenerator $screenshotGenerator;
    private Mink $mink;

    public function __construct(
        NodeEventListeningFormatter $formatter,
        ScreenshotGenerator $screenshotGenerator,
        Mink $mink
    ) {
        $this->printer = $formatter->getOutputPrinter();
        $this->screenshotGenerator = $screenshotGenerator;
        $this->mink = $mink;
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            BeforeStepTested::BEFORE => ['beforeStep'],
            AfterStepTested::AFTER   => ['afterStep'],
            OutlineTested::BEFORE    => ['beforeOutline', 1500],
            OutlineTested::AFTER     => ['afterOutline', 1500],
            ExampleTested::AFTER     => ['afterExample', 1500],
        ];
    }

    public function beforeOutline()
    {
        $this->isOutline = true;
    }

    public function afterOutline()
    {
        $this->isOutline = false;
    }

    /*
     * If behat tests failed, the last cursor position when moving the mouse is added to the screenshot.
    */
    public function beforeStep(BeforeStepTested $scope)
    {
        $script = <<<EOF
            document.head.insertAdjacentHTML('beforeend', `<style>
                body {
                    position: relative;
                }
                body:after {
                    display: block;
                    content: '';
                    width: 5px;
                    height: 5px;
                    background-color: red;
                    border-radius: 4px;
                    border: 1px solid black;
                    position: fixed;
                    z-index: 1000000;
                    top: var(--cursor-top, 0);
                    left: var(--cursor-left, 0);
                    pointer-events: none;
                }
            </style>`);
            
            window.addEventListener('mousemove', e => {
                const {body} = document;
                if (body) {
                    body.style.setProperty('--cursor-top', `\${e.pageY}px`);
                    body.style.setProperty('--cursor-left', `\${e.pageX}px`);
                }
            });
            EOF;
        try {
            $this->mink
                ->getSession()
                ->getDriver()
                ->getWebDriverSession()
                ->execute(['script' => $script, 'args' => []]);
        } catch (\Throwable $e) {
            // The cursor marker only decorates failure screenshots. Whenever the browser refuses to run
            // the script - an open JS alert blocks it, the session is gone - the step itself must still run,
            // otherwise an unrelated artifact concern aborts the whole suite.
        }
    }

    public function afterStep(AfterStepTested $scope)
    {
        if (TestResult::FAILED !== $scope->getTestResult()->getResultCode()) {
            return;
        }

        if ($this->isOutline) {
            return;
        }

        $this->saveArtifacts();
    }

    public function afterExample(AfterScenarioTested $scope)
    {
        if (TestResult::FAILED !== $scope->getTestResult()->getResultCode()) {
            return;
        }

        $this->saveArtifacts();
    }

    public function saveArtifacts()
    {
        $this->printer->writeln(sprintf('      {+%s}+-- %s{-%s}', 'pending', 'Saved artifacts:', 'pending'));
        foreach ($this->screenshotGenerator->take() as $url) {
            $this->printer->writeln('      {+pending}' . $url . '{-pending}');
        }
    }
}
