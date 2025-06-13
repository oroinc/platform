<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData\DataAccessor;

/**
 * Represents a service to load an entity during complex data import.
 */
interface ComplexDataConvertationEntityLoaderInterface
{
    /**
     * @template T
     * @psalm-param class-string<T> $entityClass
     * @psalm-return T
     */
    public function loadEntity(string $entityClass, array $criteria): ?object;
}
