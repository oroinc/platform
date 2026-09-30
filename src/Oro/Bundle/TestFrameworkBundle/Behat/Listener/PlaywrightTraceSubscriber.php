<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Mink\Mink;
use Behat\Testwork\Tester\Result\ExceptionResult;
use Behat\Testwork\Tester\Result\TestResult;
use Oro\Bundle\TestFrameworkBundle\Behat\Artifacts\FtpHandler;
use Oro\Bundle\TestFrameworkBundle\Behat\Artifacts\TraceFileRegistry;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroPlaywrightDriver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Records a Playwright trace of each feature and saves it only when a scenario of the feature failed.
 * It does nothing on a session that uses WebDriver.
 *
 * The scenarios are not isolated from each other, so one trace covers the whole feature. In the Actions panel
 * of the trace viewer, a scenario group holds the step groups, and a failed step gets a group with the failure message.
 */
class PlaywrightTraceSubscriber implements EventSubscriberInterface
{
    private ?string $scenarioLabel = null;

    /** @var array{file: string, line: int}|null */
    private ?array $scenarioLocation = null;

    private bool $featureFailed = false;

    public function __construct(
        private Mink $mink,
        private string $traceDir,
        private TraceFileRegistry $traceFileRegistry,
        private array $artifactsHandlers = []
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            ScenarioTested::BEFORE => ['beforeScenario', 5],
            ExampleTested::BEFORE => ['beforeScenario', 5],
            StepTested::BEFORE => ['beforeStep', 5],
            StepTested::AFTER => ['afterStep', 5],
            ScenarioTested::AFTER => ['afterScenario', 5],
            ExampleTested::AFTER => ['afterScenario', 5],
            FeatureTested::AFTER => ['afterFeature', 5],
        ];
    }

    public function beforeScenario(BeforeScenarioTested $event): void
    {
        $this->scenarioLabel = sprintf(
            'Scenario: %s — Feature: %s',
            $event->getScenario()->getTitle() ?: 'line ' . $event->getScenario()->getLine(),
            $event->getFeature()->getTitle() ?: basename((string)$event->getFeature()->getFile())
        );
        $this->scenarioLocation = [
            'file' => (string)$event->getFeature()->getFile(),
            'line' => $event->getScenario()->getLine(),
        ];
    }

    public function beforeStep(BeforeStepTested $event): void
    {
        if ($this->isAlertStep($event->getStep()->getText())) {
            return;
        }

        $marker = sprintf('%s %s', $event->getStep()->getKeyword(), $event->getStep()->getText());
        $location = [
            'file' => (string)$event->getFeature()->getFile(),
            'line' => $event->getStep()->getLine(),
        ];

        foreach ($this->getStartedPlaywrightDrivers() as $sessionName => $driver) {
            // A browser crash never reaches afterFeature(), so the driver saves the crash trace itself.
            $driver->setCrashTraceTarget(
                $this->traceDir,
                $this->buildTraceLabel((string)$event->getFeature()->getFile(), $sessionName)
            );
            if (null !== $this->scenarioLabel) {
                $driver->setTraceScenario($this->scenarioLabel, $this->scenarioLocation);
            }
            $driver->markTraceStep($marker, $location);
        }
    }

    public function afterStep(AfterStepTested $event): void
    {
        $result = $event->getTestResult();
        if (
            TestResult::FAILED !== $result->getResultCode()
            || $this->isAlertStep($event->getStep()->getText())
        ) {
            return;
        }

        $marker = sprintf(
            '✘ FAILED: %s %s%s',
            $event->getStep()->getKeyword(),
            $event->getStep()->getText(),
            $this->buildFailureSuffix($result)
        );
        $location = [
            'file' => (string)$event->getFeature()->getFile(),
            'line' => $event->getStep()->getLine(),
        ];

        foreach ($this->getStartedPlaywrightDrivers() as $driver) {
            if (null !== $this->scenarioLabel) {
                $driver->setTraceScenario($this->scenarioLabel, $this->scenarioLocation);
            }
            $driver->markTraceStep($marker, $location);
        }
    }

    public function afterScenario(AfterScenarioTested $event): void
    {
        if (TestResult::FAILED === $event->getTestResult()->getResultCode()) {
            $this->featureFailed = true;
        }

        $this->scenarioLabel = null;
        $this->scenarioLocation = null;

        foreach ($this->getStartedPlaywrightDrivers() as $driver) {
            $driver->endTraceScenario();
        }
    }

    /**
     * Saves or discards the feature trace, then starts a new recording.
     * A failed trace write must not fail the run. It loses the artifact only, not the features that follow.
     */
    public function afterFeature(AfterFeatureTested $event): void
    {
        $failed = $this->featureFailed;
        $this->featureFailed = false;

        foreach ($this->getStartedPlaywrightDrivers() as $sessionName => $driver) {
            try {
                if ($failed) {
                    $path = $this->buildTracePath($event, $sessionName);
                    if ($driver->saveTrace($path)) {
                        $this->traceFileRegistry->recordTrace(
                            (string)$event->getFeature()->getFile(),
                            $sessionName,
                            $path
                        );
                        if (null !== ($base = $this->getPublicBaseUrl())) {
                            $url = sprintf('%splaywright-traces/%s', $base, basename($path));
                            echo sprintf(
                                "Trace download: %s\nView it with: npx playwright show-trace %s\n",
                                $url,
                                $url
                            );
                        } else {
                            echo sprintf("View trace with: npx playwright show-trace %s\n", $path);
                        }
                    }
                } else {
                    $driver->saveTrace(null);
                }
            } catch (\Throwable $e) {
                echo sprintf("Playwright trace was not saved: %s\n", $e->getMessage());
            }

            $driver->startTracing();
        }
    }

    /**
     * @return iterable<string, OroPlaywrightDriver>
     */
    private function getStartedPlaywrightDrivers(): iterable
    {
        $property = new \ReflectionProperty(Mink::class, 'sessions');
        $sessions = $property->getValue($this->mink);

        foreach ($sessions as $name => $session) {
            if (!$session->isStarted()) {
                continue;
            }

            $driver = $session->getDriver();
            if ($driver instanceof OroPlaywrightDriver) {
                yield $name => $driver;
            }
        }
    }

    private function buildFailureSuffix(TestResult $result): string
    {
        if (!$result instanceof ExceptionResult || !$result->hasException()) {
            return '';
        }

        $message = trim((string)$result->getException()?->getMessage());
        if ('' === $message) {
            return '';
        }

        $message = preg_replace('/\s*\R\s*/u', ' | ', $message);
        if (mb_strlen($message) > 400) {
            $message = mb_substr($message, 0, 400) . '…';
        }

        return ' — ' . $message;
    }

    private function buildTracePath(AfterFeatureTested $event, string $sessionName): string
    {
        if (!is_dir($this->traceDir) && !@mkdir($this->traceDir, 0777, true) && !is_dir($this->traceDir)) {
            throw new \RuntimeException(sprintf('directory "%s" is not writable', $this->traceDir));
        }

        return sprintf(
            '%s/trace-%s-%s.zip',
            rtrim($this->traceDir, '/'),
            $this->buildTraceLabel((string)$event->getFeature()->getFile(), $sessionName),
            date('His')
        );
    }

    /**
     * The feature trace and the crash trace of the driver use the same label, so they sort together.
     */
    private function buildTraceLabel(string $featureFile, string $sessionName): string
    {
        return sprintf(
            '%s-%s',
            preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($featureFile, PATHINFO_FILENAME)),
            preg_replace('/[^a-zA-Z0-9_-]/', '_', $sessionName)
        );
    }

    /**
     * A JS alert blocks script evaluation, so an alert step gets no trace marker.
     */
    private function isAlertStep(string $stepText): bool
    {
        return str_contains($stepText, 'alert');
    }

    /**
     * Base URL of the collected job artifacts. Only the FTP handler is asked, so a local run gets null.
     */
    private function getPublicBaseUrl(): ?string
    {
        foreach ($this->artifactsHandlers as $handler) {
            if ($handler instanceof FtpHandler && null !== $handler->getBaseUrl()) {
                return $handler->getBaseUrl();
            }
        }

        return null;
    }
}
