<?php

namespace Oro\Bundle\UserBundle\Autocomplete\QueryCriteria;

use Doctrine\ORM\QueryBuilder;

/**
 * Class SearchUserCriteria
 * @package Oro\Bundle\UserBundle\Autocomplete
 */
class SearchUserCriteria extends SearchCriteria
{
    /**
     * {@inheritdoc}
     */
    public function addSearchCriteria(QueryBuilder $queryBuilder, $search)
    {
        $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like(
                        $queryBuilder->expr()->concat(
                            'u.firstName',
                            $queryBuilder->expr()->concat(
                                $queryBuilder->expr()->literal(' '),
                                'u.lastName'
                            )
                        ),
                        ':search'
                    ),
                    $queryBuilder->expr()->like(
                        $queryBuilder->expr()->concat(
                            'u.lastName',
                            $queryBuilder->expr()->concat(
                                $queryBuilder->expr()->literal(' '),
                                'u.firstName'
                            )
                        ),
                        ':search'
                    ),
                    $queryBuilder->expr()->like('u.username', ':search')
                )
            )
            ->setParameter('search', '%' . str_replace(' ', '%', $search) . '%');
    }
}
