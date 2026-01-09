<?php

namespace Oro\Bundle\LoggerBundle\Doctrine\DBAL\Logger;

use Doctrine\DBAL\Logging\SQLLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Copy of https://github.com/symfony/doctrine-bridge/blob/6.4/Logger/DbalLogger.php#L1
 *
 * Copyright (c) 2004-present Fabien Potencier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 */
class DbalLogger implements SQLLogger
{
    public const MAX_STRING_LENGTH = 32;
    public const BINARY_DATA_VALUE = '(binary value)';

    protected $logger;
    protected $stopwatch;

    public function __construct(?LoggerInterface $logger = null, ?Stopwatch $stopwatch = null)
    {
        $this->logger = $logger;
        $this->stopwatch = $stopwatch;
    }

    public function startQuery($sql, ?array $params = null, ?array $types = null): void
    {
        $this->stopwatch?->start('doctrine', 'doctrine');

        if (null !== $this->logger) {
            $this->log($sql, null === $params ? [] : $this->normalizeParams($params));
        }
    }

    public function stopQuery(): void
    {
        $this->stopwatch?->stop('doctrine');
    }

    /**
     * Logs a message.
     *
     * @return void
     */
    protected function log(string $message, array $params)
    {
        $this->logger->debug($message, $params);
    }

    private function normalizeParams(array $params): array
    {
        foreach ($params as $index => $param) {
            // normalize recursively
            if (\is_array($param)) {
                $params[$index] = $this->normalizeParams($param);
                continue;
            }

            if (!\is_string($params[$index])) {
                continue;
            }

            // non utf-8 strings break json encoding
            if (!preg_match('//u', $params[$index])) {
                $params[$index] = self::BINARY_DATA_VALUE;
                continue;
            }

            // detect if the too long string must be shorten
            if (self::MAX_STRING_LENGTH < mb_strlen($params[$index], 'UTF-8')) {
                $params[$index] = mb_substr($params[$index], 0, self::MAX_STRING_LENGTH - 6, 'UTF-8').' [...]';
                continue;
            }
        }

        return $params;
    }
}
