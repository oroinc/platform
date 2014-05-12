<?php

namespace Oro\Bundle\MigrationBundle\Migration;

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
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = array())
    {
        $this->message[] = $message;
    }
}
