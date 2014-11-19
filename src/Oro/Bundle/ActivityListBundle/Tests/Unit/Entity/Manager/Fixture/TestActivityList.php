<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Entity\Manager\Fixture;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;

class TestActivityList extends ActivityList
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
