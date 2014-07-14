<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\OrganizationBundle\Migrations\Schema\v1_0\OroOrganizationBundle;

class OroOrganizationBundleInstaller implements Installation
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
        OroOrganizationBundle::oroOrganizationTable($schema);
        OroOrganizationBundle::oroBusinessUnitTable($schema);

        OroOrganizationBundle::oroBusinessUnitForeignKeys($schema);
    }
}
