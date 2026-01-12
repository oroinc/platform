<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Psr\Log\AbstractLogger;

/**
 * A logger implementation that stores log messages in an array for later retrieval.
 *
 * This logger is useful for capturing migration and fixture execution messages without
 * writing them to a file or external service. It implements the PSR-3 LoggerInterface
 * through the {@see AbstractLogger} base class.
 */
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

    #[\Override]
    public function log($level, $message, array $context = array())
    {
        $this->message[] = $message;
    }
}
