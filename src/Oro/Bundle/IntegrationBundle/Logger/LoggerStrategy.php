<?php

namespace Oro\Bundle\IntegrationBundle\Logger;

use Psr\Log\LoggerInterface;

/**
 * Class LoggerStrategy
 *
 * @package Oro\Bundle\IntegrationBundle\Logger
 * @method LoggerStrategy emergency($message, array $context = array())
 * @method LoggerStrategy alert($message, array $context = array())
 * @method LoggerStrategy critical($message, array $context = array())
 * @method LoggerStrategy error($message, array $context = array())
 * @method LoggerStrategy warning($message, array $context = array())
 * @method LoggerStrategy info($message, array $context = array())
 * @method LoggerStrategy notice($message, array $context = array())
 * @method LoggerStrategy debug($message, array $context = array())
 * @method LoggerStrategy log($level, $message, array $context = array())
 */
class LoggerStrategy
{
    /** @var LoggerInterface */
    protected $logger;

    /**
     * Constructor allows us to pass logger when strategy is instantiating or whenever you want
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->setLogger($logger);
    }

    /**
     * Sets concrete logger
     *
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Delegate calls to concrete logger
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     * @throws \LogicException in case when logger is not set or undefined method called
     */
    public function __call($name, $args)
    {
        if (!$this->logger) {
            throw new \LogicException('Logger strategy is not configured.');
        }

        if (method_exists($this->logger, $name)) {
            return call_user_func_array([$this->logger, $name], $args);
        }

        throw new \LogicException(sprintf('Call to undefined method "%s"', $name));
    }
}
