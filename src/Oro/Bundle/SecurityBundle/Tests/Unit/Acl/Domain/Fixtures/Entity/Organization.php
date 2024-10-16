<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization as ParentOrganization;

class Organization extends ParentOrganization
{
    protected ?int $id = null;

    public function __construct($id = 0)
    {
        $this->id = $id;
    }

    #[\Override]
    public function getId()
    {
        return $this->id;
    }
}
