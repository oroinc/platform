<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v2_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\InstallerBundle\Migration\UpdateTableFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MigrateRelations implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_navigation_title',
            'route',
            'orocrm_report_',
            'oro_reportcrm_'
        ));

        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_navigation_title',
            'route',
            'oropro_organization_',
            'oro_organizationpro_'
        ));

        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_navigation_title',
            'route',
            'orocrmpro_outlook_integration',
            'oro_outlook_integration'
        ));

        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_navigation_title',
            'route',
            'orocrm',
            'oro'
        ));
    }
}
