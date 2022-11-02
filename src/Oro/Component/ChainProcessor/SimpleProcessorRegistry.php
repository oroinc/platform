<?php

namespace Oro\Component\ChainProcessor;

/**
 * The registry that can be used to create and get processors which does not depend on other services.
 * If this registry is not aware for a processor it delegates getting of this processor
 * to a specified parent registry.
 */
class SimpleProcessorRegistry implements ProcessorRegistryInterface
{
    /** @var array [processor id => processor class or [processor class, arguments], ...] */
    private $processors;

    /** @var ProcessorRegistryInterface */
    private $parentRegistry;

    public function __construct(array $processors, ProcessorRegistryInterface $parentRegistry)
    {
        $this->processors = $processors;
        $this->parentRegistry = $parentRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessor(string $processorId): ProcessorInterface
    {
        if (!isset($this->processors[$processorId])) {
            return $this->parentRegistry->getProcessor($processorId);
        }

        $processor = $this->processors[$processorId];
        if (!\is_object($processor)) {
            $processor = \is_string($processor)
                ? new $processor()
                : (new \ReflectionClass($processor[0]))->newInstanceArgs($processor[1]);
            $this->processors[$processorId] = $processor;
        }

        return $processor;
    }
}
