<?php

namespace Oro\Component\MessageQueue\Exception;

use Oro\Component\MessageQueue\Consumption\Exception\RejectMessageExceptionInterface;

/**
 * Thrown when attempting to run a job that has become stale.
 *
 * This exception is raised when a job processing attempt is made on a job that has been
 * marked as stale, indicating it has exceeded its processing window or validity period.
 * It implements {@see RejectMessageExceptionInterface} to signal that the message should be rejected
 * from the queue without redelivery, as the job is no longer valid for processing.
 */
class StaleJobRuntimeException extends \RuntimeException implements RejectMessageExceptionInterface
{
    /**
     * @return StaleJobRuntimeException
     */
    public static function create()
    {
        return new static('Stale Jobs cannot be run');
    }
}
