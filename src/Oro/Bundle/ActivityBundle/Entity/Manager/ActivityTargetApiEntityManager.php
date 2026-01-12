<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

/**
 * Provides API access to activities associated with a specific target entity.
 *
 * This manager handles API requests for retrieving activities related to a particular
 * target entity. It uses the {@see ActivityManager} to build specialized queries that return
 * only activities that are associated with the target entity, and provides methods to
 * discover which activity types and target entity types are compatible with the current entity.
 */
class ActivityTargetApiEntityManager extends ApiEntityManager
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
        return $this->activityManager->getActivitiesQueryBuilder(
            $this->class,
            $criteria,
            $joins,
            $limit,
            $page,
            $orderBy
        );
    }

    /**
     * Returns the list of FQCN of all entities which can be associated with at least one activity type
     *
     * @return string[]
     */
    public function getTargetTypes()
    {
        $targetClasses = [];
        foreach ($this->activityManager->getActivityTypes() as $activityClass) {
            $targetClasses = array_merge(
                $targetClasses,
                array_keys($this->activityManager->getActivityTargets($activityClass))
            );
        }

        return array_map(
            function ($class) {
                return ['entity' => $class];
            },
            array_unique($targetClasses)
        );
    }

    /**
     * Returns the list of FQCN of all activity entities which can be associated with the current entity
     *
     * @return string[]
     */
    public function getActivityTypes()
    {
        $activityClasses = [];
        foreach ($this->activityManager->getActivityTypes() as $activityClass) {
            $targets = $this->activityManager->getActivityTargets($activityClass);
            if (isset($targets[$this->class])) {
                $activityClasses[] = $activityClass;
            }
        }

        return array_map(
            function ($class) {
                return ['entity' => $class];
            },
            $activityClasses
        );
    }
}
