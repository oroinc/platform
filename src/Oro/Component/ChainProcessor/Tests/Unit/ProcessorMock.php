<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class ProcessorMock implements ProcessorInterface
{
    private ?string $processorId;
    /** @var callable|null */
    private $callback;

    public function __construct(?string $processorId = null, ?callable $callback = null)
    {
        $this->processorId = $processorId;
        $this->callback = $callback;
    }

    public function getProcessorId(): ?string
    {
        return $this->processorId;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        if (null !== $this->callback) {
            call_user_func($this->callback, $context);
        }
    }
}
