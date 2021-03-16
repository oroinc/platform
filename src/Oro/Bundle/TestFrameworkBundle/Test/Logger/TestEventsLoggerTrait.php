<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\Logger;

use PHPUnit\Framework\ExpectationFailedException;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Logs before and after running a test, to help developers easier find log records related to the failed test
 */
trait TestEventsLoggerTrait
{
    protected function runTest()
    {
        try {
            $this->log(
                LogLevel::INFO,
                sprintf(' ┏━━━ Started %s functional test in %s.', self::getName(true), static::class)
            );
            $testResult = parent::runTest();
            $this->log(
                LogLevel::INFO,
                sprintf(' ┗━━━ Finished %s functional test in %s.', self::getName(true), static::class)
            );

            return $testResult;
        } catch (\Throwable $exception) {
            if ($exception instanceof ExpectationFailedException) {
                $this->log(
                    LogLevel::ERROR,
                    sprintf('┗━━━ Failed %s functional test in %s. ', self::getName(true), static::class)
                );
            } else {
                $this->log(
                    LogLevel::INFO,
                    sprintf(' ┗━━━ Finished %s functional test in %s.', self::getName(true), static::class)
                );
            }

            throw $exception;
        }
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        $container = $this->getClientContainer();
        if (null === $container) {
            return;
        }

        $container->get('logger')->log($level, $message, $context);
    }

    protected function getClientContainer(): ?ContainerInterface
    {
        return self::$clientInstance ? self::$clientInstance->getContainer() : null;
    }
}
