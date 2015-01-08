<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Entity\Manager\Fixture;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class TestOrganization extends Organization
{
    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
