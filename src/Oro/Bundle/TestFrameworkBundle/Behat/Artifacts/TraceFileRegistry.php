<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

/**
 * Shared in-memory registry of the saved browser trace files of each feature.
 * PlaywrightTraceSubscriber records the traces, and FailureReportSubscriber links them in the failure report.
 */
class TraceFileRegistry
{
    /** @var array<string, array<string, string>> Feature file => session name => trace path */
    private array $traces = [];

    public function recordTrace(string $featureFile, string $sessionName, string $path): void
    {
        $this->traces[$featureFile][$sessionName] = $path;
    }

    /**
     * @return array<string, string> Session name => trace path
     */
    public function getTraces(string $featureFile): array
    {
        return $this->traces[$featureFile] ?? [];
    }
}
