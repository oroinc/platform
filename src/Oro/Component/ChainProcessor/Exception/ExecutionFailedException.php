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

    /**
     * @param string          $processorId
     * @param string|null     $action
     * @param string|null     $group
     * @param \Exception|null $previous
     */
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

    /**
     * @return string
     */
    public function getProcessorId(): string
    {
        return $this->processorId;
    }

    /**
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * @return string|null
     */
    public function getGroup(): ?string
    {
        return $this->group;
    }
}
