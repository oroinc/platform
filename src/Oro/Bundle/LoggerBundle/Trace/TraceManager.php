<?php

namespace Oro\Bundle\LoggerBundle\Trace;

use Oro\Bundle\LoggerBundle\Event\SetAppNameEvent;
use Random\Randomizer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Implementation of a manager to manage trace ID across the application
 */
class TraceManager implements TraceManagerInterface
{
    private Randomizer $randomizer;
    private ?string $traceId = null;

    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
    ) {
        $this->randomizer = new Randomizer();
    }

    #[\Override]
    public function set(?string $traceId = null): void
    {
        if (null === $traceId || !$this->validate($traceId)) {
            $traceId = $this->generate();
        }

        $this->traceId = $traceId;
        $this->dispatchSetAppEvent();
    }

    #[\Override]
    public function get(): ?string
    {
        return $this->traceId;
    }

    #[\Override]
    public function generate(): string
    {
        // When converted to hex, each byte becomes 2 characters = 32 chars total
        return bin2hex($this->randomizer->getBytes(16));
    }

    #[\Override]
    public function validate(string $traceId): bool
    {
        return (bool)preg_match('/^[a-f0-9]{32}$/', $traceId);
    }

    #[\Override]
    public function reset(): void
    {
        $this->traceId = null;
    }

    private function dispatchSetAppEvent(): void
    {
        $this->dispatcher->dispatch(new SetAppNameEvent(), SetAppNameEvent::class);
    }
}
