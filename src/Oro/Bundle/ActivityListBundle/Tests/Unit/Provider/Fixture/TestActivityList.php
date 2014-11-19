<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Provider\Fixture;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;

class TestActivityList extends ActivityList
{
    /**
     * {@inheritdoc}
     */
    public function getActivityListTargetEntities()
    {
        return [new \stdClass()];
    }

    /**
     * {@inheritdoc}
     */
    public function supportActivityListTarget($targetClass)
    {
        return true;
    }
}
