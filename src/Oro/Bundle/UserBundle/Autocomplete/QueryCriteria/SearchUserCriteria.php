<?php

namespace Oro\Bundle\UserBundle\Autocomplete\QueryCriteria;

use Doctrine\ORM\QueryBuilder;

/**
 * Implements search criteria for user autocomplete functionality.
 *
 * This class adds search conditions to query builders for finding users by name or email, supporting
 * autocomplete widgets that need to search across user first name, last name, and email address fields.
 */
class SearchUserCriteria extends SearchCriteria
{
    #[\Override]
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
