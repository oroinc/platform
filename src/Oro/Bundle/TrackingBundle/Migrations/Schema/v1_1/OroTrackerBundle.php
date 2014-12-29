<?php

namespace Oro\Bundle\TrackingBundle\Migrations\Schema\v1_1;

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
        $table = $schema->getTable('oro_tracking_event');
        $table->addColumn('code', 'string', ['length' => 255, 'notnull' => false]);
        $table->addIndex(array('code'), 'code_idx');
    }
}
