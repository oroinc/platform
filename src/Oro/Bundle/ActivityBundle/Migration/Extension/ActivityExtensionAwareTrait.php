<?php

namespace Oro\Bundle\ActivityBundle\Migration\Extension;

/**
 * This trait can be used by migrations that implement {@see ActivityExtensionAwareInterface}.
 */
trait ActivityExtensionAwareTrait
{
    private ActivityExtension $activityExtension;

    public function setActivityExtension(ActivityExtension $activityExtension): void
    {
        $this->activityExtension = $activityExtension;
    }
}
