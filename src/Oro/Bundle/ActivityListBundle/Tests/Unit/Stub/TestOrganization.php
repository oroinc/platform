<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Stub;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class TestOrganization extends Organization
{
    /**
     * @param int $id
     */
    #[\Override]
    public function setId($id)
    {
        $this->id = $id;
    }
}
