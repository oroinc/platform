<?php

namespace Oro\Bundle\ActivityListBundle\Migration\Extension;

/**
 * ActivityListExtensionAwareInterface should be implemented by migrations that depends on a ActivityListExtension.
 */
interface ActivityListExtensionAwareInterface
{
    /**
     * Sets the ActivityExtension
     */
    public function setActivityListExtension(ActivityListExtension $activityListListExtension);
}
