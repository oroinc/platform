<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Exception;

use Exception;
use Oro\Component\MessageQueue\Consumption\Exception\RejectMessageExceptionInterface;

/**
 * Exception encountered during when the data in messages are invalid and can not be used for processing or missing
 */
class InvalidSecurityTokenException extends Exception implements RejectMessageExceptionInterface
{
    /**
     * @var string
     */
    protected $message = 'Security token is invalid';
}
