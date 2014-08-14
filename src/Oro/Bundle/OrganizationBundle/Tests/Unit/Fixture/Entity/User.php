<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity;

use Oro\Bundle\UserBundle\Entity\User as ParentUser;

class User extends ParentUser
{
    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
