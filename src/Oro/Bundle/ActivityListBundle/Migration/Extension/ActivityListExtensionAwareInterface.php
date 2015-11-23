<?php

namespace Oro\Bundle\ActivityListBundle\Migration\Extension;

/**
 * ActivityListExtensionAwareInterface should be implemented by migrations that depends on a ActivityListExtension.
 */
interface ActivityListExtensionAwareInterface
{
    /**
     * Sets the ActivityExtension
     *
     * @param ActivityListExtension $activityListListExtension
     */
    public function setActivityListExtension(ActivityListExtension $activityListListExtension);
}
