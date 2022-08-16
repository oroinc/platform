<?php

namespace Oro\Bundle\UserBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

/**
 * The autocomplete handler to search roles.
 * This class can be removed after the support of access rules for search will be implemented (BAP-17347).
 */
class AuthenticatedRolesHandler extends SearchHandler
{
    /**
     * {@inheritdoc}
     */
    protected function findById($query)
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('r')
            ->andWhere('r.id IN (:entityIds)')
            ->setParameter('entityIds', explode(',', $query));

        return $this->aclHelper->apply($queryBuilder)->getResult();
    }

    /**
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('r')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);
        if ($search) {
            $queryBuilder
                ->andWhere('r.label LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $this->aclHelper->apply($queryBuilder)->getResult();
    }
}
