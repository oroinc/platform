<?php

namespace Oro\Bundle\ActivityListBundle\Entity\Factory;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;

/**
 * The factory to create instance of activity list class.
 */
class ActivityListFactory
{
    public function createActivityList(): ActivityList
    {
        return new ActivityList();
    }
}
