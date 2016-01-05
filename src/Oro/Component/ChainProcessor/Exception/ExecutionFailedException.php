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
    public function __construct($processorId, $action = null, $group = null, \Exception $previous = null)
    {
        $this->processorId = $processorId;
        $this->action      = $action;
        $this->group       = $group;
        $this->processorId = $processorId;

        $message = sprintf('Processor failed: "%s".', $processorId);
        if (null !== $previous) {
            $message .= sprintf(' Reason: %s', $previous->getMessage());
        }

        parent::__construct($message, 0, $previous);
    }

    /**
     * @return string
     */
    public function getProcessorId()
    {
        return $this->processorId;
    }

    /**
     * @return string|null
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return string|null
     */
    public function getGroup()
    {
        return $this->group;
    }
}
