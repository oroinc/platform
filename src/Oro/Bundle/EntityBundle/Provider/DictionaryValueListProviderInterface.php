<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\QueryBuilder;

interface DictionaryValueListProviderInterface
{
    /**
     * Checks whether the provider supports a given entity
     *
     * @param string $className The FQCN of an entity
     *
     * @return bool TRUE if this provider supports the given entity; otherwise, FALSE
     */
    public function supports($className);

    /**
     * Gets a query builder for getting dictionary item values for a given dictionary class
     *
     * @param string $className The FQCN of a dictionary entity
     *
     * @return QueryBuilder
     */
    public function getValueListQueryBuilder($className);

    /**
     * Gets the configuration of the entity serializer for a given dictionary class
     *
     * @param string $className The FQCN of a dictionary entity
     *
     * @return array
     */
    public function getSerializationConfig($className);

    /**
     * Gets a list of entity classes supported by this provider
     *
     * @return string[]
     */
    public function getSupportedEntityClasses();
}
