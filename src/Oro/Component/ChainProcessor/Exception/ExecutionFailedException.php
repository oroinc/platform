<?php

namespace Oro\Component\ChainProcessor\Exception;

/**
 * Represents an execution error if a processor is executed in a chain.
 */
class ExecutionFailedException extends \RuntimeException
{
    /** @var string */
    private $processorId;

    /** @var string|null */
    private $action;

    /** @var string|null */
    private $group;

    public function __construct(
        string $processorId,
        string $action = null,
        string $group = null,
        \Exception $previous = null
    ) {
        $this->processorId = $processorId;
        $this->action = $action;
        $this->group = $group;

        $message = sprintf('Processor failed: "%s".', $processorId);
        if (null !== $previous) {
            $message .= sprintf(' Reason: %s', $previous->getMessage());
        }

        parent::__construct($message, 0, $previous);
    }

    public function getProcessorId(): string
    {
        return $this->processorId;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }
}
