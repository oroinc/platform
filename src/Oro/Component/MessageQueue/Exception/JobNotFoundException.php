<?php

namespace Oro\Component\MessageQueue\Exception;

use Oro\Component\MessageQueue\Consumption\Exception\RejectMessageExceptionInterface;

/**
 * Should be triggered in case when there is no job with provided id
 * and processed message with that id should be rejected.
 */
class JobNotFoundException extends \LogicException implements RejectMessageExceptionInterface
{
    /**
     * @param int|string $jobId
     * @return JobNotFoundException
     */
    public static function create($jobId)
    {
        return new static(sprintf('Job was not found. id: "%s"', $jobId));
    }
}
