<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData\DataAccessor;

/**
 * Represents a service to access data during complex data import and export.
 */
interface ComplexDataConvertationDataAccessorInterface
{
    public function getFieldValue(object $entity, string $propertyPath): mixed;

    public function getLookupFieldValue(object $entity, ?string $lookupFieldName, ?string $entityClass): mixed;

    public function findEntityId(string $entityClass, ?string $lookupFieldName, mixed $lookupFieldValue): mixed;

    /**
     * @template T
     * @psalm-param class-string<T> $entityClass
     * @psalm-return T
     */
    public function findEntity(string $entityClass, ?string $lookupFieldName, mixed $lookupFieldValue): ?object;
}
