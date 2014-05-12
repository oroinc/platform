<?php

namespace Oro\Bundle\SecurityBundle\Exception;

/**
 * Exception thrown if an operation is forbidden for some reason.
 */
class ForbiddenException extends \RuntimeException
{
    /**
     * @var string
     */
    protected $reason;

    /**
     * @param string $reason
     */
    public function __construct($reason)
    {
        parent::__construct(sprintf('An operation is forbidden. Reason: %s', $reason));
        $this->reason = $reason;
    }

    /**
     * Gets forbidden reason.
     *
     * @return string
     */
    final public function getReason()
    {
        return $this->reason;
    }
}
