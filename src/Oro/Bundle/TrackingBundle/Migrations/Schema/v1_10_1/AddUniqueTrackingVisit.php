<?php

namespace Oro\Bundle\TrackingBundle\Migrations\Schema\v1_10_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\TrackingBundle\Migration\FillUniqueTrackingVisitsQuery;

class AddUniqueTrackingVisit implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if (!$schema->hasTable('oro_tracking_unique_visit')) {
            $this->createOroTrackingUniqueVisitTable($schema);
            $this->addOroTrackingUniqueVisitForeignKeys($schema);
            $queries->addQuery(new FillUniqueTrackingVisitsQuery());
        }
    }

    /**
     * Create oro_tracking_unique_visit table
     *
     * @param Schema $schema
     */
    protected function createOroTrackingUniqueVisitTable(Schema $schema)
    {
        $table = $schema->createTable('oro_tracking_unique_visit');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('visit_count', 'integer', []);
        $table->addColumn('user_identifier', 'string', ['length' => 32]);
        $table->addColumn('action_date', 'date', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['website_id', 'action_date'], 'uvisit_action_date_idx', []);
        $table->addIndex(['user_identifier', 'action_date'], 'uvisit_user_by_date_idx', []);
    }

    /**
     * Add oro_tracking_unique_visit foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroTrackingUniqueVisitForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_tracking_unique_visit');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_tracking_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
