<?php

namespace Oro\Bundle\UserBundle\Autocomplete;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;
use Oro\Bundle\UserBundle\Entity\User;

class AuthenticatedRolesHandler extends SearchHandler
{
    /**
     * {@inheritdoc}
     */
    protected function findById($query)
    {
        $entityIds = explode(',', $query);

        $queryBuilder = $this->getBasicQueryBuilder();
        $queryBuilder->andWhere($queryBuilder->expr()->in('r.id', $entityIds));

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        $queryBuilder = $this->getBasicQueryBuilder();
        if ($search) {
            $this->addSearchCriteria($queryBuilder, $search);
        }
        $queryBuilder
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Returns query builder for roles.
     *
     * @return QueryBuilder
     */
    protected function getBasicQueryBuilder()
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('r');
        $queryBuilder->andWhere('r.role != :anonymous')
            ->setParameter('anonymous', User::ROLE_ANONYMOUS);

        return $queryBuilder;
    }

    /**
     * Adds search criteria to query builder.
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $search
     */
    protected function addSearchCriteria(QueryBuilder $queryBuilder, $search)
    {
        $queryBuilder->andWhere($queryBuilder->expr()->like('r.label', ':search'))
            ->setParameter('search', '%' . $search . '%');
    }
}
