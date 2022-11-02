<?php

namespace Oro\Bundle\BatchBundle\Exception;

use Oro\Bundle\BatchBundle\Job\BatchStatus;

/**
 * Exception to indicate the the job has been interrupted. The exception state
 * indicated is not normally recoverable by batch application clients, but
 * internally it is useful to force a check. The exception will often be wrapped
 * in a runtime exception (usually UnexpectedJobExecutionException} before
 * reaching the client.
 *
 * @author    Benoit Jacquemont <benoit@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/MIT MIT
 */
class JobInterruptedException extends \Exception
{
    private BatchStatus $status;

    /**
     * @param string $message
     * @param integer $code
     * @param \Exception|null $previous
     * @param BatchStatus|null $status Status of the batch when the exception occurred
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Exception $previous = null,
        ?BatchStatus $status = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->status = $status ?: new BatchStatus(BatchStatus::STOPPED);
    }

    /**
     * The desired status of the surrounding execution after the interruption.
     *
     * @return BatchStatus the status of the interruption (default STOPPED)
     */
    public function getStatus(): BatchStatus
    {
        return $this->status;
    }
}
