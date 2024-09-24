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
    protected bool                $isOutline = false;
    protected OutputPrinter       $printer;
    protected ScreenshotGenerator $screenshotGenerator;
    private Mink                  $mink;

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
     * If behat tests failed, the last cursor position when moving the mouse is added to the screenshot,
     * except for situations when there are alerts on the page
    */
    public function beforeStep(BeforeStepTested $scope)
    {
        /*
         * Because alert is synchronous in JS, no code can be executed while an alert is displayed.
         * Steps that expect an alert to appear, because the data was changed, not saved,
         * and went to another page, get an alert.
         * Since the alert blocks this beforeStep, the cursor is not fixed for such steps.
         * */
        if (str_contains($scope->getStep()->getText(), 'should see alert with message')
            || str_contains($scope->getStep()->getText(), 'accept alert')
        ) {
            return;
        }
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
        $this->mink
            ->getSession()
            ->getDriver()
            ->getWebDriverSession()
            ->execute(['script' => $script, 'args' => []]);
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
