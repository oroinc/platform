<?php

namespace Oro\Bundle\TrackingBundle\Migrations\Schema\v1_7;

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
        $table = $schema->getTable('oro_tracking_visit_event');
        if ($table->hasForeignKey('FK_B39EEE8F71F7E88B')) {
            $table->removeForeignKey('FK_B39EEE8F71F7E88B');
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_tracking_event_dictionary'),
                ['event_id'],
                ['id'],
                ['onUpdate' => null, 'onDelete' => 'CASCADE']
            );
        }
    }
}
