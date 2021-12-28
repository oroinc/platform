<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit as ParentBU;

class BusinessUnit extends ParentBU
{
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }
}
