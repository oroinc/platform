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
        $table = $schema->getTable('oro_dashboard');
        $table->addColumn('createdAt', 'datetime');
        $table->addColumn('updatedAt', 'datetime', ['notnull' => false]);
    }
}
