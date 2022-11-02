<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Stub;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Role;

class RoleStub extends Role
{
    protected ?Organization $organization = null;

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param Organization $value
     *
     * @return $this
     */
    public function setOrganization($value)
    {
        $this->organization = $value;

        return $this;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }
}
