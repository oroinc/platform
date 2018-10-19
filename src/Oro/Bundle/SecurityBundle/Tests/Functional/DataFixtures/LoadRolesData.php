<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractLoadAclData;

class LoadRolesData extends AbstractLoadAclData
{
    public function getDependencies()
    {
        return ['@OroSecurityBundle/Tests/Functional/DataFixtures/load_test_data.yml'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataPath()
    {
        return '@OroSecurityBundle/Tests/Functional/DataFixtures/role_permissions.yml';
    }
}
