<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization as ParentOrganization;

class Organization extends ParentOrganization
{
    protected $id;

    public function __construct($id = 0)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }
}
