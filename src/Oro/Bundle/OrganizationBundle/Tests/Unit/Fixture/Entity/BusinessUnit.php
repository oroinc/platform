<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit as ParentBU;

class BusinessUnit extends ParentBU
{
    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
