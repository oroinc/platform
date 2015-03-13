<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Schema\v1_4;

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
        $table = $schema->getTable('oro_dashboard_widget_state');
        $table->addColumn('options', 'array', ['comment' => '(DC2Type:array)', 'notnull' => false]);
    }
}
