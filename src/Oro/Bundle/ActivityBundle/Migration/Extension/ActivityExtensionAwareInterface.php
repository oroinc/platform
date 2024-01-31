<?php

namespace Oro\Bundle\ActivityBundle\Migration\Extension;

/**
 * This interface should be implemented by migrations that depend on {@see ActivityExtension}.
 */
interface ActivityExtensionAwareInterface
{
    public function setActivityExtension(ActivityExtension $activityExtension);
}
