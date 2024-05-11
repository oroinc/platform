<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\QueryBuilder;

/**
 * Represents a service that provides information about dictionaries.
 */
interface DictionaryValueListProviderInterface
{
    /**
     * Checks whether the provider supports the given entity.
     */
    public function supports(string $className): bool;

    /**
     * Gets a query builder for getting dictionary item values for the given dictionary class.
     */
    public function getValueListQueryBuilder(string $className): QueryBuilder;

    /**
     * Gets the configuration of the entity serializer for the given dictionary class.
     */
    public function getSerializationConfig(string $className): array;

    /**
     * Gets a list of entity classes supported by this provider.
     *
     * @return string[]
     */
    public function getSupportedEntityClasses(): array;
}
