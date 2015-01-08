<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity;

use Oro\Bundle\UserBundle\Entity\User as ParentUser;

class User extends ParentUser
{
    protected $owner;

    public function __construct($id = 0, $owner = null, $organization = null)
    {
        $this->id = $id;
        $this->owner = $owner;
        $this->setOrganization($organization);
        parent::__construct();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getOwner()
    {
        return $this->owner;
    }
}
