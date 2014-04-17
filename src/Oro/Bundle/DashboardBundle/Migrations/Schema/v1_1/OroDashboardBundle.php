<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroDashboardBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // added field "is_default"
        $table = $schema->getTable('oro_dashboard');
        $table->addColumn('is_default', 'boolean', ['default' => '0']);
        $table->addIndex(['is_default'], 'dashboard_is_default_idx');

		// added fields "createdAt" and "updatedAt"
        $table->addColumn('createdAt', 'datetime');
        $table->addColumn('updatedAt', 'datetime', ['notnull' => false]);
    }
}
