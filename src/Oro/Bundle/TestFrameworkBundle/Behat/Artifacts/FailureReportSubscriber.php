<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Mink\Mink;
use Behat\Testwork\Tester\Result\ExceptionResult;
use Behat\Testwork\Tester\Result\TestResult;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroPlaywrightDriver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Writes a Markdown failure report for every failed scenario and adds it to the failure indexes.
 * The reports and the indexes go into the trace directory, next to the trace archives that they link.
 *
 * The listener priorities matter. afterStep runs after PrettyArtifactsSubscriber, and afterFeature runs after
 * PlaywrightTraceSubscriber (priority 5). Thus the report reuses the screenshot and the saved trace archive.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class FailureReportSubscriber implements EventSubscriberInterface
{
    private ?string $scenarioTitle = null;

    private int $scenarioLine = 0;

    private ?FailureContext $currentFailure = null;

    /** @var FailureContext[] */
    private array $featureFailures = [];

    /**
     * @param ArtifactsHandlerInterface[] $artifactsHandlers
     */
    public function __construct(
        private Mink $mink,
        private ScreenshotGenerator $screenshotGenerator,
        private TraceFileRegistry $traceFileRegistry,
        private FailureReportRenderer $renderer,
        private string $reportDir,
        private string $logDir,
        private array $artifactsHandlers = []
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            ScenarioTested::BEFORE => ['beforeScenario', 10],
            ExampleTested::BEFORE => ['beforeScenario', 10],
            StepTested::AFTER => ['afterStep', -100],
            ScenarioTested::AFTER => ['afterScenario', -100],
            ExampleTested::AFTER => ['afterScenario', -100],
            FeatureTested::AFTER => ['afterFeature', 0],
        ];
    }

    public function beforeScenario(BeforeScenarioTested $event): void
    {
        $this->scenarioTitle = $event->getScenario()->getTitle();
        $this->scenarioLine = $event->getScenario()->getLine();
        $this->currentFailure = null;
        $this->screenshotGenerator->reset();
    }

    public function afterStep(AfterStepTested $event): void
    {
        $result = $event->getTestResult();
        if (TestResult::FAILED !== $result->getResultCode() || null !== $this->currentFailure) {
            return;
        }

        $failure = new FailureContext();
        $failure->featureFile = (string)$event->getFeature()->getFile();
        $failure->featureTitle = $event->getFeature()->getTitle();
        $failure->scenarioTitle = $this->scenarioTitle;
        $failure->scenarioLine = $this->scenarioLine ?: $event->getStep()->getLine();
        $failure->stepText = sprintf('%s %s', $event->getStep()->getKeyword(), $event->getStep()->getText());
        $failure->stepLine = $event->getStep()->getLine();
        $failure->exception = $this->extractException($result);
        $failure->pageUrl = $this->getCurrentPageUrl();
        $failure->jsErrors = $this->collectJsErrors();
        $failure->screenshotUrls = $this->screenshotGenerator->getLastUrls();
        $failure->ariaSnapshot = $this->getAriaSnapshot();
        $failure->featureSource = is_file($failure->featureFile)
            ? (file_get_contents($failure->featureFile) ?: null)
            : null;
        $failure->failedAt = date('Y-m-d H:i:s');

        $prodLog = rtrim($this->logDir, '/') . '/prod.log';
        $failure->prodLogPath = is_file($prodLog) ? $prodLog : null;

        $this->currentFailure = $failure;
    }

    public function afterScenario(AfterScenarioTested $event): void
    {
        $failure = $this->currentFailure;
        $this->currentFailure = null;
        $this->scenarioTitle = null;
        $this->scenarioLine = 0;

        // HealerProcessor can heal a failed step and leave the scenario green. Such a scenario gets no report.
        if (null === $failure || TestResult::FAILED !== $event->getTestResult()->getResultCode()) {
            return;
        }

        // PrettyArtifactsSubscriber after a step, or ProgressArtifactsSubscriber after a scenario, usually took
        // the screenshot already. Take one only when no formatter subscriber did.
        if (!$failure->screenshotUrls) {
            $failure->screenshotUrls = $this->screenshotGenerator->getLastUrls();
        }
        if (!$failure->screenshotUrls) {
            try {
                $failure->screenshotUrls = $this->screenshotGenerator->take();
            } catch (\Throwable) {
            }
        }

        $this->featureFailures[] = $failure;
    }

    public function afterFeature(AfterFeatureTested $event): void
    {
        $failures = $this->featureFailures;
        $this->featureFailures = [];

        if (!$failures) {
            return;
        }

        $featureFile = (string)$event->getFeature()->getFile();
        $tracePaths = $this->traceFileRegistry->getTraces($featureFile);

        // A failed report write must not fail the run. It loses the report only, not the features that follow.
        foreach ($failures as $failure) {
            try {
                $this->saveFailureReport($failure, $tracePaths);
            } catch (\Throwable $e) {
                echo sprintf("Failure report was not saved: %s\n", $e->getMessage());
            }
        }
    }

    /**
     * @param array<string, string> $tracePaths
     */
    private function saveFailureReport(FailureContext $failure, array $tracePaths): void
    {
        if (!is_dir($this->reportDir) && !@mkdir($this->reportDir, 0777, true) && !is_dir($this->reportDir)) {
            throw new \RuntimeException(sprintf('directory "%s" is not writable', $this->reportDir));
        }

        $failure->tracePaths = $tracePaths;
        $failure->artifactsBaseUrl = $this->getPublicBaseUrl();

        $reportPath = $this->buildReportPath($failure);
        $content = $this->renderer->renderReport($failure);
        if (false === @file_put_contents($reportPath, $content)) {
            throw new \RuntimeException(sprintf('cannot write "%s"', $reportPath));
        }
        $reportUrls = $this->publishReport($content, basename($reportPath));
        $this->appendIndexRow($failure, basename($reportPath), $reportUrls);

        foreach ($reportUrls as $url) {
            echo sprintf("Failure report download: %s\n", $url);
        }
        if (null !== $failure->artifactsBaseUrl) {
            echo sprintf("Prod log: %sprod.log\n", $failure->artifactsBaseUrl);
            echo sprintf("All artifacts: %s\n", $failure->artifactsBaseUrl);
        }
    }

    /**
     * Base URL of the collected job artifacts, which hold the whole log directory.
     * It asks every ArtifactsFileHandlerInterface handler, because the container build uses the local handler.
     * If only the FTP handler is asked, the report shows container paths instead of links.
     */
    private function getPublicBaseUrl(): ?string
    {
        foreach ($this->artifactsHandlers as $handler) {
            $baseUrl = $handler instanceof ArtifactsFileHandlerInterface ? $handler->getBaseUrl() : null;
            if (null !== $baseUrl) {
                return $baseUrl;
            }
        }

        return null;
    }

    /**
     * Publishes the report through the same artifacts handlers as the failure screenshots.
     * Thus the console output has a download URL both locally and on CI.
     * The published name gets a random suffix, because parallel CI jobs share the artifacts storage.
     *
     * @return string[] Download URLs
     */
    private function publishReport(string $content, string $fileName): array
    {
        if (!$this->artifactsHandlers) {
            return [];
        }

        $publishedName = sprintf(
            '%s-%s.md',
            pathinfo($fileName, PATHINFO_FILENAME),
            bin2hex(random_bytes(4))
        );

        $urls = [];
        foreach ($this->artifactsHandlers as $handler) {
            try {
                if ($handler instanceof FtpHandler) {
                    // The job artifacts already hold the report, so link to that copy instead of an upload.
                    $base = $handler->getBaseUrl();
                    if (null !== $base) {
                        $urls[] = sprintf('%splaywright-traces/%s', $base, $fileName);
                    }
                    continue;
                }

                if ($handler instanceof ArtifactsFileHandlerInterface) {
                    $urls[] = $handler->saveFile($content, $publishedName);
                }
            } catch (\Throwable) {
            }
        }

        return array_filter($urls);
    }

    private function extractException(TestResult $result): string
    {
        if (!$result instanceof ExceptionResult || !$result->hasException()) {
            return '';
        }

        return trim((string)$result->getException()?->getMessage());
    }

    private function getCurrentPageUrl(): ?string
    {
        try {
            $session = $this->mink->getSession();
            if (!$session->isStarted()) {
                return null;
            }

            return $session->getDriver()->getCurrentUrl();
        } catch (\Throwable) {
            return null;
        }
    }

    private function getAriaSnapshot(): ?string
    {
        try {
            $driver = $this->mink->getSession()->getDriver();
            if ($driver instanceof OroPlaywrightDriver) {
                return $driver->getAriaSnapshot();
            }
        } catch (\Throwable) {
        }

        foreach ($this->getStartedPlaywrightDrivers() as $driver) {
            return $driver->getAriaSnapshot();
        }

        return null;
    }

    /**
     * @return array<array{session: string, type: string, message: string, time: string}>
     */
    private function collectJsErrors(): array
    {
        $errors = [];
        foreach ($this->getStartedPlaywrightDrivers() as $sessionName => $driver) {
            foreach ($driver->getCollectedJsErrors() as $error) {
                $errors[] = [
                    'session' => $sessionName,
                    'type' => (string)($error['type'] ?? ''),
                    'message' => (string)($error['message'] ?? ''),
                    'time' => (string)($error['time'] ?? ''),
                ];
            }
        }

        return $errors;
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

    private function buildReportPath(FailureContext $failure): string
    {
        $feature = pathinfo($failure->featureFile, PATHINFO_FILENAME);

        return sprintf(
            '%s/failure-%s-%d-%s.md',
            rtrim($this->reportDir, '/'),
            preg_replace('/[^a-zA-Z0-9_-]/', '_', $feature),
            $failure->scenarioLine,
            date('His')
        );
    }

    /**
     * The index files use an exclusive lock, because parallel Behat processes write to them.
     *
     * @param string[] $reportUrls
     */
    private function appendIndexRow(FailureContext $failure, string $reportFileName, array $reportUrls = []): void
    {
        $this->appendToIndexFile(
            'REPORT.md',
            $this->renderer->renderIndexHeader(),
            $this->renderer->renderIndexRow($failure, $reportFileName, $reportUrls)
        );
        $this->appendToIndexFile(
            'REPORT.html',
            $this->renderer->renderHtmlIndexHeader(),
            $this->renderer->renderHtmlIndexRow($failure, $reportFileName)
        );
    }

    private function appendToIndexFile(string $fileName, string $header, string $row): void
    {
        $handle = fopen(rtrim($this->reportDir, '/') . '/' . $fileName, 'c');
        if (false === $handle) {
            return;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return;
            }

            fseek($handle, 0, SEEK_END);
            if (0 === ftell($handle)) {
                fwrite($handle, $header);
            }
            fwrite($handle, $row);
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }
}
