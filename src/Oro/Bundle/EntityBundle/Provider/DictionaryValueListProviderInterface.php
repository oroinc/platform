<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\SqlQueryBuilder;

interface DictionaryValueListProviderInterface
{
    /**
     * Gets a query builder for getting dictionary item values for a given dictionary class
     *
     * @param string $className The FQCN of a dictionary entity
     *
     * @return QueryBuilder|SqlQueryBuilder|null QueryBuilder or SqlQueryBuilder if the provider can get values
     *                                           NULL if the provider cannot process the given entity
     */
    public function getValueListQueryBuilder($className);

    /**
     * Returns the configuration of the entity serializer for a given dictionary class
     *
     * @param string $className The FQCN of a dictionary entity
     *
     * @return array|null
     */
    public function getSerializationConfig($className);

    /**
     * Gets a list of entity classes supported by this provider
     *
     * @return string[]
     */
    public function getSupportedEntityClasses();
}
