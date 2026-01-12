<?php

namespace Oro\Bundle\UserBundle\Autocomplete;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Autocomplete\QueryCriteria\SearchCriteria;

/**
 * Autocomplete search handler for users in the current organization.
 *
 * Extends {@see UserSearchHandler} to return enabled users assigned to the current organization,
 * excluding the current user. Uses {@see SearchCriteria} for search filtering and {@see TokenAccessorInterface}
 * for organization and user context. Does not use ACL checks as they are not required.
 */
class OrganizationUsersHandler extends UserSearchHandler
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var SearchCriteria */
    protected $searchUserCriteria;

    public function setTokenAccessor(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    public function setSearchUserCriteria(SearchCriteria $searchCriteria)
    {
        $this->searchUserCriteria = $searchCriteria;
    }

    #[\Override]
    protected function findById($query)
    {
        $entityIds = explode(',', $query);

        $queryBuilder = $this->getBasicQueryBuilder();
        $queryBuilder->andWhere($queryBuilder->expr()->in('u.id', ':entityIds'))
            ->setParameter('entityIds', $entityIds);

        return $queryBuilder->getQuery()->getResult();
    }

    #[\Override]
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        $queryBuilder = $this->getBasicQueryBuilder();
        if ($search) {
            $this->searchUserCriteria->addSearchCriteria($queryBuilder, $search);
        }
        $queryBuilder
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Returns query builder that uses to build query for search bu id or by search string.
     * Result data limit by users that was have access to the current organization and excluding current user.
     *
     * @return QueryBuilder
     */
    protected function getBasicQueryBuilder()
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('u');
        $queryBuilder->join('u.organizations', 'org')
            ->andWhere('org.id = :org')
            ->andWhere('u.id != :currentUser')
            ->andWhere('u.enabled = :enabled')
            ->setParameter('org', $this->tokenAccessor->getOrganizationId())
            ->setParameter('currentUser', $this->tokenAccessor->getUserId())
            ->setParameter('enabled', true);

        return $queryBuilder;
    }
}
