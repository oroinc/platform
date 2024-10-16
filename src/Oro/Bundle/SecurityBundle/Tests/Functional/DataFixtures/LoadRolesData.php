<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractLoadAclData;

class LoadRolesData extends AbstractLoadAclData
{
    #[\Override]
    public function getDependencies()
    {
        return ['@OroSecurityBundle/Tests/Functional/DataFixtures/load_test_data.yml'];
    }

    #[\Override]
    protected function getDataPath()
    {
        return '@OroSecurityBundle/Tests/Functional/DataFixtures/data/role_permissions.yml';
    }
}
