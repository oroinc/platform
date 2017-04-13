<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\OutlineTested;
use Behat\Mink\Mink;
use Behat\Testwork\Output\NodeEventListeningFormatter;
use Behat\Testwork\Output\Printer\OutputPrinter;
use Behat\Testwork\Tester\Result\TestResult;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PrettyArtifactsSubscriber implements EventSubscriberInterface
{
    /**
     * @var bool
     */
    protected $isOutline;

    /**
     * @var OutputPrinter
     */
    protected $printer;

    /**
     * @var ArtifactsHandlerInterface[]
     */
    protected $artifactsHandlers;

    /**
     * @var Mink
     */
    protected $mink;

    /**
     * @param NodeEventListeningFormatter $formatter
     * @param Mink $mink
     */
    public function __construct(NodeEventListeningFormatter $formatter, Mink $mink)
    {
        $this->printer = $formatter->getOutputPrinter();
        $this->mink = $mink;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            AfterStepTested::AFTER => ['afterStep'],
            OutlineTested::BEFORE  => ['beforeOutline', 1500],
            OutlineTested::AFTER   => ['afterOutline', 1500],
            ExampleTested::AFTER   => ['afterExample', 1500],
        ];
    }

    /**
     * @param ArtifactsHandlerInterface $artifactsHandler
     */
    public function addArtifactHandler(ArtifactsHandlerInterface $artifactsHandler)
    {
        $this->artifactsHandlers[] = $artifactsHandler;
    }

    public function beforeOutline()
    {
        $this->isOutline = true;
    }

    public function afterOutline()
    {
        $this->isOutline = false;
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
        foreach ($this->artifactsHandlers as $artifactsHandler) {
            $this->printer->writeln(
                '      {+pending}'.$artifactsHandler->save($this->mink->getSession()->getScreenshot()).'{-pending}'
            );
        }
    }
}
