<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData\Mapping;

/**
 * Loads data mapping for complex data import and export.
 */
class ComplexDataMappingProvider
{
    private ?array $mapping = null;

    /**
     * @param iterable<ComplexDataMappingProviderInterface> $mappingProviders
     */
    public function __construct(
        private readonly iterable $mappingProviders
    ) {
    }

    public function getMapping(): array
    {
        if (null === $this->mapping) {
            $mapping = [];
            foreach ($this->mappingProviders as $mappingProvider) {
                $mapping = $mappingProvider->getMapping($mapping);
            }
            $this->mapping = $mapping;
        }

        return $this->mapping;
    }
}
