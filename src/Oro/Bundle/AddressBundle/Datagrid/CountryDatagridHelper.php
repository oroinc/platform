<?php

namespace Oro\Bundle\AddressBundle\Datagrid;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Provides a method to get a callback that returns a query builder for a country datagrid filter.
 */
class CountryDatagridHelper
{
    public function getCountryFilterQueryBuilder(): callable
    {
        return function (EntityRepository $repository): QueryBuilder {
            return $repository->createQueryBuilder('c')
                ->orderBy('c.name', 'ASC');
        };
    }
}
