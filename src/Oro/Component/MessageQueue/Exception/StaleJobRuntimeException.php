<?php

namespace Oro\Component\MessageQueue\Exception;

class StaleJobRuntimeException extends \RuntimeException
{
    /**
     * @return StaleJobRuntimeException
     */
    public static function create()
    {
        return new static('Stale Jobs cannot be run');
    }
}
