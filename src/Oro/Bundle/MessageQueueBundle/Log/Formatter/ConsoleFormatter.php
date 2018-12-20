<?php

namespace Oro\Bundle\MessageQueueBundle\Log\Formatter;

use Symfony\Bridge\Monolog\Formatter\ConsoleFormatter as BaseConsoleFormatter;

/**
 * Formats message queue consumer related log records for the console output
 * by coloring them depending on log level.
 */
class ConsoleFormatter extends BaseConsoleFormatter
{
    const SIMPLE_FORMAT =
        "%datetime% %start_tag%%channel%.%level_name%%end_tag%: %message%%context%%extra%\n";
    const SIMPLE_DATE = 'Y-m-d H:i:s';

    /**
     * {@inheritdoc}
     */
    public function __construct(array $options = [])
    {
        $options = array_replace([
            'format' => self::SIMPLE_FORMAT,
            'date_format' => self::SIMPLE_DATE,
            'multiline' => false,
            'colors' => false
        ], $options);

        parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $formatted = parent::format($record);

        return preg_replace('/\s+<\/>:/', '</>:', $formatted);
    }
}
