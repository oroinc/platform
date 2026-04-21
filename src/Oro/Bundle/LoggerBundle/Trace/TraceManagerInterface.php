<?php

namespace Oro\Bundle\LoggerBundle\Trace;

/**
 * Interface for managing trace ID across the application
 * Provides methods to store and retrieve trace ID
 */
interface TraceManagerInterface
{
    public function set(string $traceId): void;

    public function get(): ?string;

    public function reset(): void;

    public function generate(): string;

    public function validate(string $traceId): bool;
}
