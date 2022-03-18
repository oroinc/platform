<?php

namespace Oro\Component\MessageQueue\Exception;

use Oro\Component\MessageQueue\Consumption\Exception\RejectMessageExceptionInterface;
use Oro\Component\MessageQueue\Job\Job;

/**
 * Thrown when a job cannot be started.
 */
class JobCannotBeStartedException extends \RuntimeException implements RejectMessageExceptionInterface
{
    private Job $job;

    public function __construct(Job $job, string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            $message ?: sprintf(
                'Job "%s" cannot be started because it is already in status "%s"',
                $job->getId(),
                $job->getStatus()
            ),
            $code,
            $previous
        );

        $this->job = $job;
    }

    public function getJob(): Job
    {
        return $this->job;
    }
}
