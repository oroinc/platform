<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

/**
 * Provides API access to activity entities and their associated target entities.
 *
 * This manager extends the base API entity manager to handle activity-specific operations,
 * including retrieving available activity types and their compatible target entity types.
 * It leverages the {@see ActivityManager} to determine which entities can be associated with
 * specific activity types, enabling flexible activity management through the API.
 */
class ActivityApiEntityManager extends ApiEntityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    public function __construct(ObjectManager $om, ActivityManager $activityManager)
    {
        parent::__construct(null, $om);
        $this->activityManager = $activityManager;
    }

    /**
     * Returns the list of FQCN of all activity entities
     *
     * @return string[]
     */
    public function getActivityTypes()
    {
        return array_map(
            function ($class) {
                return ['entity' => $class];
            },
            $this->activityManager->getActivityTypes()
        );
    }

    /**
     * Returns the list of FQCN of all activity entities
     *
     * @return string[]
     */
    public function getActivityTargetTypes()
    {
        return array_map(
            function ($class) {
                return ['entity' => $class];
            },
            array_keys($this->activityManager->getActivityTargets($this->class))
        );
    }
}
