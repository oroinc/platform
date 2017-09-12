<?php

namespace Oro\Component\MessageQueue\Exception;

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
