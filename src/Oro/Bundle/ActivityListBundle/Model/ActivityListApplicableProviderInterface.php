<?php

namespace Oro\Bundle\ActivityListBundle\Model;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;

/**
 * A temporary solution to avoid BC breaks in maintenance version.
 * Will be removed in version 5.1 and isActivityListApplicable() will be moved in {@see ActivityListProviderInterface}.
 */
interface ActivityListApplicableProviderInterface
{
    /**
     * Checks whether the given item is applicable to be added to the activity list.
     */
    public function isActivityListApplicable(ActivityList $activityList): bool;
}
