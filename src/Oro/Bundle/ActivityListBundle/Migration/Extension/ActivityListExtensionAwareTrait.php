<?php

namespace Oro\Bundle\ActivityListBundle\Migration\Extension;

/**
 * This trait can be used by migrations that implement {@see ActivityListExtensionAwareInterface}.
 */
trait ActivityListExtensionAwareTrait
{
    private ActivityListExtension $activityListExtension;

    public function setActivityListExtension(ActivityListExtension $activityListExtension): void
    {
        $this->activityListExtension = $activityListExtension;
    }
}
