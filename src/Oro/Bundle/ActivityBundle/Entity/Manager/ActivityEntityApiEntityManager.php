<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

/**
 * Provides API access to entities that can be associated with activities.
 *
 * This manager handles API requests for retrieving activity target entities (entities that
 * can be associated with activities). It overrides the query builder generation to use the
 * {@see ActivityManager}'s specialized query building logic, ensuring that only valid activity
 * targets are returned and properly filtered according to activity association rules.
 */
class ActivityEntityApiEntityManager extends ApiEntityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    public function __construct(ObjectManager $om, ActivityManager $activityManager)
    {
        parent::__construct(null, $om);
        $this->activityManager = $activityManager;
    }

    #[\Override]
    public function getListQueryBuilder($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        return $this->activityManager->getActivityTargetsQueryBuilder(
            $this->class,
            $criteria,
            $joins,
            $limit,
            $page,
            $orderBy
        );
    }
}
