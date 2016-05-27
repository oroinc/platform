<?php

namespace Oro\Bundle\UserBundle\Autocomplete;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * This user search handler return users that was assigned to current organization and limit by search string
 * excluding current user.
 * This handler does not use ACL helper and search engine because we does not needed any ACL checks
 *
 * Class OrganizationUsersHandler
 * @package Oro\Bundle\UserBundle\Autocomplete
 */
class OrganizationUsersHandler extends UserSearchHandler
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    protected function findById($query)
    {
        $entityIds = explode(',', $query);

        $queryBuilder = $this->getBasicQueryBuilder();
        $queryBuilder->andWhere($queryBuilder->expr()->in('u.id', $entityIds));

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
            ->setParameter('org', $this->securityFacade->getOrganizationId())
            ->setParameter('currentUser', $this->securityFacade->getLoggedUserId())
            ->setParameter('enabled', true);

        return $queryBuilder;
    }

    /**
     * Adds a search criteria to the given query builder based on the given query string
     *
     * @param QueryBuilder $queryBuilder The query builder
     * @param string       $search       The search string
     */
    protected function addSearchCriteria(QueryBuilder $queryBuilder, $search)
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
