<?php

namespace Oro\Bundle\ActivityBundle\Migration\Extension;

/**
 * ActivityExtensionAwareInterface should be implemented by migrations that depends on a ActivityExtension.
 */
interface ActivityExtensionAwareInterface
{
    /**
     * Sets the ActivityExtension
     *
     * @param ActivityExtension $activityExtension
     */
    public function setActivityExtension(ActivityExtension $activityExtension);
}
