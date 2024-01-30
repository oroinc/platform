<?php

namespace Oro\Bundle\ActivityListBundle\Migration\Extension;

/**
 * This interface should be implemented by migrations that depend on {@see ActivityListExtension}.
 */
interface ActivityListExtensionAwareInterface
{
    public function setActivityListExtension(ActivityListExtension $activityListExtension);
}
