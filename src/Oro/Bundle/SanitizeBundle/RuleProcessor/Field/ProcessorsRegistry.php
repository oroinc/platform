<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Field;

/**
 * Field sanitizing rule processors registry.
 */
class ProcessorsRegistry
{
    private array $processors = [];

    public function __construct(iterable $processors)
    {
        foreach ($processors as $processorName => $processor) {
            if (!$processor instanceof ProcessorInterface) {
                throw new \InvalidArgumentException(sprintf(
                    'Field/column sanitize processor must be instance of "%s" but instance of "%s" was given',
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
                'Field/column sanitize rule porcessor "%s" is not registered',
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
