<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Entity;

/**
 * Entity sanitizing rule processors registry.
 */
class ProcessorsRegistry
{
    private array $processors = [];

    public function __construct(iterable $processors)
    {
        foreach ($processors as $processorName => $processor) {
            if (!$processor instanceof ProcessorInterface) {
                throw new \InvalidArgumentException(sprintf(
                    'Entity sanitizing processor must be an instance of "%s", but an instance of "%s" was given',
                    ProcessorInterface::class,
                    \get_class($processor)
                ));
            }

            $this->processors[$processorName] = $processor;
        }
    }

    public function get(string $processorName): ProcessorInterface
    {
        if (!isset($this->processors[$processorName])) {
            throw new \InvalidArgumentException(sprintf(
                'Entity sanitizing rule processor "%s" is not registered',
                $processorName
            ));
        }

        return $this->processors[$processorName];
    }

    public function getProcessorAliases(): array
    {
        return array_keys($this->processors);
    }
}
