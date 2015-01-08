<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Entity\Manager\Fixture;

use Oro\Bundle\UserBundle\Entity\User;

class TestUser extends User
{
    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
