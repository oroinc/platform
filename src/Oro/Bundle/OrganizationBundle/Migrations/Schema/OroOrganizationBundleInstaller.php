<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use Oro\Bundle\OrganizationBundle\Migrations\Schema\v1_0\OroOrganizationBundle as OroOrganizationBundleV1_0;
use Oro\Bundle\OrganizationBundle\Migrations\Schema\v1_2\OroOrganizationBundle as OroOrganizationBundleV1_2;

class OroOrganizationBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_2';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        OroOrganizationBundleV1_0::oroOrganizationTable($schema);
        OroOrganizationBundleV1_0::oroBusinessUnitTable($schema);

        OroOrganizationBundleV1_0::oroBusinessUnitForeignKeys($schema);

        OroOrganizationBundleV1_2::updateOrganizationTable($schema);
        OroOrganizationBundleV1_2::updateBusinessUnitTable($schema);
    }
}
