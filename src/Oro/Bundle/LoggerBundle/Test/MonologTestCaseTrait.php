<?php

namespace Oro\Bundle\LoggerBundle\Test;

use Monolog\Logger;

/**
 * Provides methods to test monolog logs handle
 * Inspired by {@see \Monolog\Test\TestCase}
 */
trait MonologTestCaseTrait
{
    protected function getLogRecord($level = Logger::WARNING, $message = 'test', array $context = []): array
    {
        return [
            'message' => (string)$message,
            'context' => $context,
            'level' => $level,
            'level_name' => Logger::getLevelName($level),
            'channel' => 'test',
            'datetime' => \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true))),
            'extra' => [],
        ];
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
