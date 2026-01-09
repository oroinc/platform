<?php

namespace Oro\Component\MessageQueue\Exception;

/**
 * Thrown when a job needs to be redelivered for processing.
 *
 * This exception signals that a job encountered a condition requiring it to be redelivered
 * to the message queue for another processing attempt. It is used to trigger the redelivery
 * mechanism, allowing jobs to be retried when transient failures occur.
 */
class JobRedeliveryException extends \Exception
{
    /**
     * @return JobRedeliveryException
     */
    public static function create()
    {
        return new static('Job needs to be redelivered');
    }
}
