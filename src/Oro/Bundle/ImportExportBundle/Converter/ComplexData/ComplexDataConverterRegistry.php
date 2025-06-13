<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData;

use Psr\Container\ContainerInterface;

/**
 * The registry that allows to get an additional entity converter for a specific entity.
 */
class ComplexDataConverterRegistry
{
    private array $entityTypeConverters = [];
    private array $entityTypeReverseConverters = [];
    private array $entityErrorConverters = [];

    public function __construct(
        private readonly array $converters,
        private readonly ContainerInterface $container
    ) {
    }

    public function getConverterForEntity(string $entityType): ?ComplexDataConverterInterface
    {
        if (!\array_key_exists($entityType, $this->entityTypeConverters)) {
            $converters = $this->getConverters($entityType, ComplexDataConverterInterface::class);
            $converter = null;
            if ($converters) {
                $converter = \count($converters) > 1
                    ? new ChainComplexDataConverter($converters)
                    : $converters[0];
            }
            $this->entityTypeConverters[$entityType] = $converter;
        }

        return $this->entityTypeConverters[$entityType];
    }

    public function getReverseConverterForEntity(string $entityType): ?ComplexDataReverseConverterInterface
    {
        if (!\array_key_exists($entityType, $this->entityTypeReverseConverters)) {
            $converters = $this->getConverters($entityType, ComplexDataReverseConverterInterface::class);
            $converter = null;
            if ($converters) {
                $converter = \count($converters) > 1
                    ? new ChainComplexDataReverseConverter($converters)
                    : $converters[0];
            }
            $this->entityTypeReverseConverters[$entityType] = $converter;
        }

        return $this->entityTypeReverseConverters[$entityType];
    }

    public function getErrorConverterForEntity(string $entityType): ?ComplexDataErrorConverterInterface
    {
        if (!\array_key_exists($entityType, $this->entityErrorConverters)) {
            $converters = $this->getConverters($entityType, ComplexDataErrorConverterInterface::class);
            $converter = null;
            if ($converters) {
                $converter = \count($converters) > 1
                    ? new ChainComplexDataErrorConverter($converters)
                    : $converters[0];
            }
            $this->entityErrorConverters[$entityType] = $converter;
        }

        return $this->entityErrorConverters[$entityType];
    }

    private function getConverters(string $entityType, string $converterInterface): array
    {
        $converters = [];
        foreach ($this->converters as [$converterServiceId, $converterEntityType]) {
            if ($converterEntityType === $entityType) {
                $converterService = $this->container->get($converterServiceId);
                if ($converterService instanceof $converterInterface) {
                    $converters[] = $converterService;
                }
            }
        }

        return $converters;
    }
}
