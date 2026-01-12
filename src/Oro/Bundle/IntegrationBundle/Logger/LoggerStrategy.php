<?php

namespace Oro\Bundle\IntegrationBundle\Logger;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Logger strategy for integration bundle that allows to log memory usage
 */
class LoggerStrategy implements LoggerInterface
{
    use LoggerAwareTrait;

    public const FORMAT = '[%.2F MiB/%.2F MiB] %s';

    /** @var bool */
    protected $debug;

    /**
     * Constructor allows us to pass logger when strategy is instantiating or whenever you want
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->setLogger($logger ?: new NullLogger());
    }

    #[\Override]
    public function emergency($message, array $context = [])
    {
        $this->logger->emergency($this->buildMessage($message), $context);
    }

    #[\Override]
    public function alert($message, array $context = [])
    {
        $this->logger->alert($this->buildMessage($message), $context);
    }

    #[\Override]
    public function critical($message, array $context = [])
    {
        $this->logger->critical($this->buildMessage($message), $context);
    }

    #[\Override]
    public function error($message, array $context = [])
    {
        $this->logger->error($this->buildMessage($message), $context);
    }

    #[\Override]
    public function warning($message, array $context = [])
    {
        $this->logger->warning($this->buildMessage($message), $context);
    }

    #[\Override]
    public function notice($message, array $context = [])
    {
        $this->logger->notice($this->buildMessage($message), $context);
    }

    #[\Override]
    public function info($message, array $context = [])
    {
        $this->logger->info($this->buildMessage($message), $context);
    }

    #[\Override]
    public function debug($message, array $context = [])
    {
        $this->logger->debug($this->buildMessage($message), $context);
    }

    #[\Override]
    public function log($level, $message, array $context = [])
    {
        $this->logger->log($level, $this->buildMessage($message), $context);
    }

    /**
     * @param boolean $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * @param string $message
     * @return string
     */
    protected function buildMessage($message)
    {
        if ($this->debug) {
            return sprintf(
                self::FORMAT,
                memory_get_usage(true) / 1024 / 1024,
                memory_get_peak_usage(true) / 1024 / 1024,
                $message
            );
        }

        return $message;
    }
}
