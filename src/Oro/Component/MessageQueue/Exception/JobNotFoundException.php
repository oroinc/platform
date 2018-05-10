<?php

namespace Oro\Component\MessageQueue\Exception;

use Oro\Component\MessageQueue\Consumption\Exception\RejectMessageExceptionInterface;

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
