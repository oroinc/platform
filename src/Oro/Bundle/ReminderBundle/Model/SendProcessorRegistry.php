<?php

namespace Oro\Bundle\ReminderBundle\Model;

use Oro\Bundle\ReminderBundle\Exception\MethodNotSupportedException;

/**
 * Sends processor registry
 */
class SendProcessorRegistry
{
    /**
     * @var SendProcessorInterface[]
     */
    protected $processors;

    /**
     * @param SendProcessorInterface[] $processors
     */
    public function __construct(array $processors)
    {
        $this->processors = array();
        foreach ($processors as $processor) {
            $this->processors[$processor->getName()] = $processor;
        }
    }

    /**
     * Get processor by method
     *
     * @param string $method
     * @return SendProcessorInterface
     * @throws MethodNotSupportedException If processor is not supported
     */
    public function getProcessor($method)
    {
        if (!isset($this->processors[$method])) {
            throw new MethodNotSupportedException(sprintf('Reminder method "%s" is not supported.', $method));
        }

        return $this->processors[$method];
    }

    /**
     * Get associative array of processor labels.
     *
     * @return array
     */
    public function getProcessorLabels()
    {
        $result = array();
        foreach ($this->processors as $name => $processor) {
            $result[$name] = $processor->getLabel();
        }
        return $result;
    }
}
