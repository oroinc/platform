<?php

namespace Oro\Bundle\ApiBundle\Collector;

/**
 * Collects warnings during API documentation processing.
 *
 * This service accumulates warning messages that occur during the API documentation
 * generation process. It provides a controlled collection mechanism that can be
 * started and stopped on demand, ensuring warnings are only collected when needed.
 */
class ApiDocWarningsCollector
{
    /** @var string[] Collection of warning messages */
    private array $warnings = [];

    private bool $isCollecting = false;

    public function startCollecting(): void
    {
        $this->warnings = [];
        $this->isCollecting = true;
    }

    public function stopCollecting(): void
    {
        $this->isCollecting = false;
    }

    public function addWarning(string $message): void
    {
        if (!$this->isCollecting) {
            return;
        }

        $this->warnings[] = $message;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
