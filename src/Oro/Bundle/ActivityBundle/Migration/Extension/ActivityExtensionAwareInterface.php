<?php

namespace Oro\Bundle\ActivityBundle\Migration\Extension;

/**
 * ActivityExtensionAwareInterface should be implemented by migrations that depends on a ActivityExtension.
 */
interface ActivityExtensionAwareInterface
{
    /**
     * Sets the ActivityExtension
     */
    public function setActivityExtension(ActivityExtension $activityExtension);
}
