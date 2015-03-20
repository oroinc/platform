<?php

namespace Oro\Bundle\TrackingBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTrackerBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroTrackingVisitTable($schema);
    }

    /**
     * Create oro_tracking_visit table
     *
     * @param Schema $schema
     */
    protected function updateOroTrackingVisitTable(Schema $schema)
    {
        $table = $schema->getTable('oro_tracking_visit');
        $table->addColumn('client', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('client_type', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('client_version', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('os', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('os_version', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('desktop', 'boolean', ['notnull' => false]);
        $table->addColumn('mobile', 'boolean', ['notnull' => false]);
        $table->addColumn('bot', 'boolean', ['notnull' => false]);
    }
}
