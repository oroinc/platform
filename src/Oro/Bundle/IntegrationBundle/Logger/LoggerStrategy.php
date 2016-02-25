<?php

namespace Oro\Bundle\IntegrationBundle\Logger;

use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Class LoggerStrategy
 */
class LoggerStrategy implements LoggerInterface
{
    const FORMAT = '[%.2F MiB/%.2F MiB] %s';

    use LoggerAwareTrait;

    /** @var bool */
    protected $debug;

    /**
     * Constructor allows us to pass logger when strategy is instantiating or whenever you want
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->setLogger($logger ?: new NullLogger());
    }

    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = [])
    {
        return $this->logger->emergency($this->buildMessage($message), $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = [])
    {
        return $this->logger->alert($this->buildMessage($message), $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = [])
    {
        return $this->logger->critical($this->buildMessage($message), $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = [])
    {
        return $this->logger->error($this->buildMessage($message), $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = [])
    {
        return $this->logger->warning($this->buildMessage($message), $context);
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = [])
    {
        return $this->logger->notice($this->buildMessage($message), $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = [])
    {
        return $this->logger->info($this->buildMessage($message), $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = [])
    {
        return $this->logger->debug($this->buildMessage($message), $context);
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        return $this->logger->log($level, $this->buildMessage($message), $context);
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
