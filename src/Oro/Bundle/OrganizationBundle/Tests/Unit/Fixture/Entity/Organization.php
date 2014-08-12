<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization as ParentOrganization;

class Organization extends ParentOrganization
{
    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
