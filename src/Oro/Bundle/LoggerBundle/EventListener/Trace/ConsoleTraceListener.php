<?php

namespace Oro\Bundle\LoggerBundle\EventListener\Trace;

use Oro\Bundle\LoggerBundle\Trace\TraceManagerInterface;

/**
 * Listener to set a unique trace ID for each command run.
 */
class ConsoleTraceListener
{
    public function __construct(
        private readonly TraceManagerInterface $traceManager,
        private readonly ?string $traceConsole,
    ) {
    }

    public function onConsoleCommand(): void
    {
        if (null !== $this->traceManager->get()) {
            return;
        }

        $this->traceManager->set($this->traceConsole);
    }
}
