<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Fixtures;

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
