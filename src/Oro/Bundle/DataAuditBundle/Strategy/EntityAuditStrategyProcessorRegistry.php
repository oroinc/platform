<?php

namespace Oro\Bundle\DataAuditBundle\Strategy;

use Oro\Bundle\DataAuditBundle\Strategy\Processor\EntityAuditStrategyProcessorInterface;

/**
 * To collect all entity audit inverse strategy processor with their entity name.
 */
class EntityAuditStrategyProcessorRegistry
{
    /**
     * @var EntityAuditStrategyProcessorInterface[]
     */
    protected array $processors = [];

    protected EntityAuditStrategyProcessorInterface $defaultProcessor;

    public function __construct(EntityAuditStrategyProcessorInterface $processor)
    {
        $this->defaultProcessor = $processor;
    }

    public function addProcessor(EntityAuditStrategyProcessorInterface $processor, string $className): void
    {
        if ($this->hasProcessor($className)) {
            throw new \LogicException(sprintf(
                'You should not override an existed strategy processor for entity "%s".',
                $className
            ));
        }

        $this->processors[$className] = $processor;
    }

    /**
     * @return EntityAuditStrategyProcessorInterface[]
     */
    public function getProcessors(): array
    {
        return $this->processors;
    }

    public function getDefaultProcessor(): EntityAuditStrategyProcessorInterface
    {
        return $this->defaultProcessor;
    }

    public function getProcessor($className): ?EntityAuditStrategyProcessorInterface
    {
        if ($this->hasProcessor($className)) {
            return $this->processors[$className];
        }

        return null;
    }

    public function hasProcessor(string $className): bool
    {
        return array_key_exists($className, $this->processors);
    }
}
