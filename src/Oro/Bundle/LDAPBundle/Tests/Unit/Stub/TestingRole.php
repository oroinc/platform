<?php

namespace Oro\Bundle\LDAPBundle\Tests\Unit\Stub;

use Oro\Bundle\UserBundle\Entity\Role;

class TestingRole extends Role
{
    /**
     * @param string $role
     * @param int $id
     */
    public function __construct($role = '', $id = null)
    {
        parent::__construct($role);
        $this->id = $id;
    }
}
