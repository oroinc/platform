<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Psr\Log\AbstractLogger;

class ArrayLogger extends AbstractLogger
{
    /**
     * @var string[]
     */
    protected $message = [];

    /**
     * Returns logged messages
     *
     * @return string[]
     */
    public function getMessages()
    {
        return $this->message;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        $this->message[] = $message;
    }
}
