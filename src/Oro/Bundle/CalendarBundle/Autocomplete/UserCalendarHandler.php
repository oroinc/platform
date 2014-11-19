<?php

namespace Oro\Bundle\CalendarBundle\Autocomplete;

use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\UserBundle\Autocomplete\UserAclHandler;

class UserCalendarHandler extends UserAclHandler
{
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        list ($search, $entityClass, $permission, $entityId, $excludeCurrentUser) = explode(';', $query);
        $entityClass = $this->decodeClassName($entityClass);

        $hasMore  = false;
        $object   = $entityId
            ? $this->em->getRepository($entityClass)->find((int)$entityId)
            : 'entity:' . $entityClass;
        $observer = new OneShotIsGrantedObserver();
        $this->aclVoter->addOneShotIsGrantedObserver($observer);
        if ($this->getSecurityContext()->isGranted($permission, $object)) {
            $results = [];
            if ($searchById) {
                $results[] = $this->em->getRepository($entityClass)->find((int)$query);
            } else {
                $page        = (int)$page > 0 ? (int)$page : 1;
                $perPage     = (int)$perPage > 0 ? (int)$perPage : 10;
                $firstResult = ($page - 1) * $perPage;
                $perPage += 1;

                $user         = $this->getSecurityContext()->getToken()->getUser();
                $organization = $this->getSecurityContext()->getToken()->getOrganizationContext();
                $queryBuilder = $this->getSearchQueryBuilder($search);
                if ((boolean)$excludeCurrentUser) {
                    $this->excludeUser($queryBuilder, $user);
                }
                $queryBuilder
                    ->setFirstResult($firstResult)
                    ->setMaxResults($perPage);
                $this->addAcl($queryBuilder, $observer->getAccessLevel(), $user, $organization);
                $results = $queryBuilder->getQuery()->getResult();

                $hasMore = count($results) == $perPage;
            }

            $resultsData = [];
            foreach ($results as $user) {
                $resultsData[] = $this->convertItem($user);
            }
        } else {
            $resultsData = [];
        }

        return [
            'results' => $resultsData,
            'more'    => $hasMore
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchQueryBuilder($search)
    {
        $qb = parent::getSearchQueryBuilder($search);
        $qb
            ->select('calendar')
            ->innerJoin('OroCalendarBundle:Calendar', 'calendar', 'WITH', 'calendar.owner = users');

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($calendar)
    {
        $result = parent::convertItem($calendar->getOwner());
        $result['id'] = $calendar->getId();

        return $result;
    }
}
