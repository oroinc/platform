<?php

namespace Oro\Bundle\LoggerBundle\Test;

use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;

/**
 * Provides methods to test monolog logs handle
 * Inspired by {@see \Monolog\Test\TestCase}
 */
trait MonologTestCaseTrait
{
    protected function getLogRecord($level = Logger::WARNING, $message = 'test', array $context = []): LogRecord
    {
        $levelEnum = is_int($level) ? Level::fromValue($level) : Level::fromName($level);

        return new LogRecord(
            datetime: \DateTimeImmutable::createFromFormat('U.u', sprintf('%.6F', microtime(true))),
            channel: 'test',
            level: $levelEnum,
            message: (string)$message,
            context: $context,
            extra: []
        );
    }

    protected function getMultipleLogRecords(): array
    {
        return [
            $this->getLogRecord(Logger::DEBUG, 'debug message 1'),
            $this->getLogRecord(Logger::DEBUG, 'debug message 2'),
            $this->getLogRecord(Logger::INFO, 'information'),
            $this->getLogRecord(Logger::WARNING, 'warning'),
            $this->getLogRecord(Logger::ERROR, 'error'),
        ];
    }
}
