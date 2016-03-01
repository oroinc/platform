<?php

namespace Oro\Component\ChainProcessor;

class ChainProcessorFactory implements ProcessorFactoryInterface
{
    /** @var array */
    private $factories = [];

    /** @var ProcessorFactoryInterface[]|null */
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
        $this->sortedFactories        = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessor($processorId)
    {
        $processor = null;
        $factories = $this->getFactories();
        foreach ($factories as $factory) {
            $processor = $factory->getProcessor($processorId);
            if (null !== $processor) {
                break;
            }
        }

        return $processor;
    }

    /**
     * @return ProcessorFactoryInterface[]
     */
    protected function getFactories()
    {
        if (null === $this->sortedFactories) {
            krsort($this->factories);
            $this->sortedFactories = call_user_func_array('array_merge', $this->factories);
        }

        return $this->sortedFactories;
    }
}
