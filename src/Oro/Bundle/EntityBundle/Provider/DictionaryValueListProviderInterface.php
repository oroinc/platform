<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\SqlQueryBuilder;

interface DictionaryValueListProviderInterface
{
    /**
     * Gets query builder for getting dictionary item values for the given dictionary class
     *
     * @param string $className
     *
     * @return QueryBuilder|SqlQueryBuilder|null  QueryBuilder or SqlQueryBuilder if the provider can get value list
     *                                            NULL if the provider can not get the value list for this dictionary
     */
    public function getValueListQueryBuilder($className);
    /**
     * Gets list of supported dictionary entity classes
     *
     * @return array
     */
    public function getSupportedEntityClasses();
}
