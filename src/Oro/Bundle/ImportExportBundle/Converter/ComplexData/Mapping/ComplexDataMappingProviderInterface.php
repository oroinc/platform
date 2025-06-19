<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData\Mapping;

/**
 * Represents a service to load data mapping for complex data import and export.
 */
interface ComplexDataMappingProviderInterface
{
    public function getMapping(array $mapping): array;
}
