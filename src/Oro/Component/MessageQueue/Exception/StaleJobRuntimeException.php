<?php

namespace Oro\Component\MessageQueue\Exception;

use Oro\Component\MessageQueue\Consumption\Exception\RejectMessageExceptionInterface;

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
