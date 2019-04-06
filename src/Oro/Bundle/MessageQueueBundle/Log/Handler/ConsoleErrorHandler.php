<?php

namespace Oro\Bundle\MessageQueueBundle\Log\Handler;

use Monolog\Handler\BufferHandler as BaseBufferHandler;

/**
 * Buffers all records until flush was not triggered and then pass them as batch.
 * Together with \Oro\Bundle\MessageQueueBundle\EventListener\ConsoleErrorListener
 * write all logs on `console.error` event for the last queue message
 */
class ConsoleErrorHandler extends BaseBufferHandler
{
    /**
     * {@inheritdoc}
     */
    public function close()
    {
        // override close() method to avoid to send logs when handler was closed without errors
    }
}
