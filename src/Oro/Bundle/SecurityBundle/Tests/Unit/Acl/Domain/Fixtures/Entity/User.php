<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity;

use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as ParentUser;

class User extends ParentUser
{
    public function __construct($id = 0, $owner = null, $organization = null)
    {
        $this->id = $id;
        $this->owner = $owner;
        $this->setOrganization($organization);
        parent::__construct();
    }
}
