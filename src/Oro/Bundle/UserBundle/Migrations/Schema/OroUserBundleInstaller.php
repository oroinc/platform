<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\UserBundle\Migrations\Schema\v1_0\OroUserBundle;
use Oro\Bundle\UserBundle\Migrations\Schema\v1_1\UserEmailOrigins;

class OroUserBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        OroUserBundle::oroAccessGroupTable($schema);
        OroUserBundle::oroAccessRoleTable($schema);
        OroUserBundle::oroSessionTable($schema);
        OroUserBundle::oroUserTable($schema, false);
        OroUserBundle::oroUserAccessGroupTable($schema);
        OroUserBundle::oroUserAccessGroupRoleTable($schema);
        OroUserBundle::oroUserAccessRoleTable($schema);
        OroUserBundle::oroUserApiTable($schema);
        OroUserBundle::oroUserBusinessUnitTable($schema);
        OroUserBundle::oroUserEmailTable($schema);
        OroUserBundle::oroUserStatusTable($schema);
        UserEmailOrigins::oroUserEmailOriginTable($schema);

        OroUserBundle::oroAccessGroupForeignKeys($schema);
        OroUserBundle::oroAccessRoleForeignKeys($schema);
        OroUserBundle::oroUserForeignKeys($schema, false);
        OroUserBundle::oroUserAccessGroupForeignKeys($schema);
        OroUserBundle::oroUserAccessGroupRoleForeignKeys($schema);
        OroUserBundle::oroUserAccessRoleForeignKeys($schema);
        OroUserBundle::oroUserApiForeignKeys($schema);
        OroUserBundle::oroUserBusinessUnitForeignKeys($schema);
        OroUserBundle::oroUserEmailForeignKeys($schema);
        OroUserBundle::oroUserStatusForeignKeys($schema);
        UserEmailOrigins::oroUserEmailOriginForeignKeys($schema);

        OroUserBundle::addOwnerToOroEmailAddress($schema);
    }
}
