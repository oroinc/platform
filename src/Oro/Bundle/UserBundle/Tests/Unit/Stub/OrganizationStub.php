<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Stub;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class OrganizationStub extends Organization
{
    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
