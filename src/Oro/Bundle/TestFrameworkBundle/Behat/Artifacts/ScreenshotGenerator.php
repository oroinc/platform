<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

use Behat\Mink\Mink;

/**
 * Generates screenshots for Behat tests
 */
class ScreenshotGenerator
{
    /** @var string[] */
    private array $lastUrls = [];

    /**
     * @param Mink $mink
     * @param ArtifactsHandlerInterface[] $artifactsHandlers
     */
    public function __construct(
        private Mink $mink,
        private array $artifactsHandlers
    ) {
    }

    public function take(): array
    {
        try {
            $screenshot = $this->mink->getSession()->getScreenshot();
        } catch (\Throwable $e) {
            // The browser can refuse to produce a screenshot - an open JS alert blocks it, the session died.
            // Reporting that is preferable to letting a diagnostic aid abort the run that produced the failure.
            return [sprintf('Screenshot is not available: %s', $e->getMessage())];
        }

        $urls = [];
        foreach ($this->artifactsHandlers as $artifactsHandler) {
            $urls[] = $artifactsHandler->save($screenshot);
        }

        $this->lastUrls = $urls;

        return $urls;
    }

    /**
     * URLs from the last take() call, so several failure listeners can share one screenshot.
     * FailureReportSubscriber calls reset() when a scenario starts.
     *
     * @return string[]
     */
    public function getLastUrls(): array
    {
        return $this->lastUrls;
    }

    public function reset(): void
    {
        $this->lastUrls = [];
    }
}
