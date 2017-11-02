<?php

namespace Oro\Component\ChainProcessor;

/**
 * A factory that can be used to create processors which does not depend on other services.
 */
class SimpleProcessorFactory implements ProcessorFactoryInterface
{
    /** @var string[] */
    protected $processors = [];

    /**
     * Registers processors.
     * This method was created by performance reasons and it is intended only to set initial set of processors.
     * If the bag already contains any processors all of them will be lost.
     *
     * @param array $processors [processor id => processor class, ...]
     */
    public function setProcessors(array $processors)
    {
        $this->processors = $processors;
    }

    /**
     * Registers a processor.
     *
     * @param string $processorId
     * @param string $processorClass
     */
    public function addProcessor($processorId, $processorClass)
    {
        $this->processors[$processorId] = $processorClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessor($processorId)
    {
        if (!isset($this->processors[$processorId])) {
            return null;
        }

        $processor = $this->processors[$processorId];
        if (!is_object($processor)) {
            $processor = new $processor();

            $this->processors[$processorId] = $processor;
        }

        return $processor;
    }
}
