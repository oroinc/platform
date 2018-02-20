<?php

namespace Oro\Component\ChainProcessor;

/**
 * Delegates creation of a processor to child factories.
 */
class ChainProcessorFactory implements ProcessorFactoryInterface
{
    /** @var array */
    private $factories = [];

    /** @var ProcessorFactoryInterface[] */
    private $sortedFactories;

    /**
     * Registers a factory in the chain
     *
     * @param ProcessorFactoryInterface $factory
     * @param int                       $priority
     */
    public function addFactory(ProcessorFactoryInterface $factory, $priority = 0)
    {
        $this->factories[$priority][] = $factory;
        // sort by priority and flatten
        // we do it here due to performance reasons (it is expected that it will be only several factories,
        // but the getProcessor method will be called a lot of times)
        \krsort($this->factories);
        $this->sortedFactories = \array_merge(...$this->factories);
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessor($processorId)
    {
        $processor = null;
        foreach ($this->sortedFactories as $factory) {
            $processor = $factory->getProcessor($processorId);
            if (null !== $processor) {
                break;
            }
        }

        return $processor;
    }
}
