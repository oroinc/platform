<?php

namespace Oro\Component\MessageQueue\Log\Processor;

/**
 * This processor replaces the record channel with the channel stored
 * in the context under the "log_channel" key.
 */
class RestoreOriginalChannelProcessor
{
    const LOG_CHANNEL_KEY = 'log_channel';
    const CONTEXT_KEY     = 'context';

    /**
     * Replaces the record channel with the channel stored in the context.
     *
     * @param array $record
     *
     * @return array
     */
    public function __invoke(array $record)
    {
        if (isset($record[self::CONTEXT_KEY][self::LOG_CHANNEL_KEY])) {
            /**
             * substitute the record channel with the channel stored in the record context
             * @see \Oro\Component\MessageQueue\Log\Handler\AbstractResendHandler::handle
             */
            $record['channel'] = $record[self::CONTEXT_KEY][self::LOG_CHANNEL_KEY];
            unset($record[self::CONTEXT_KEY][self::LOG_CHANNEL_KEY]);
        }

        return $record;
    }
}
