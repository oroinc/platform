<?php

namespace Oro\Bundle\TrackingBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SecurityBundle\Migrations\Schema\UpdateOwnershipTypeQuery;
use Oro\Bundle\TrackingBundle\Migration\Extension\IdentifierEventExtension;
use Oro\Bundle\TrackingBundle\Migration\Extension\IdentifierEventExtensionAwareInterface;


class OroTrackerBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
//        $this->createOroTrackingVisitTable($schema);
//        $this->createOroTrackingVisitEventTable($schema);
//        $this->addOroTrackingVisitEventForeignKeys($schema);
//
//       // $this->extension->addIdentifierAssociation($schema,'orocrm_magento_customer');
    }

    /**
     * Create oro_tracking_visit table
     *
     * @param Schema $schema
     */
    protected function createOroTrackingVisitTable(Schema $schema)
    {
        $table = $schema->createTable('oro_tracking_visit');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('visitor', 'string', ['length' => 255]);
        $table->addColumn('ip', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_tracking_visit_event table
     *
     * @param Schema $schema
     */
    protected function createOroTrackingVisitEventTable(Schema $schema)
    {
        $table = $schema->createTable('oro_tracking_visit_event');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('web_event_id', 'integer', ['notnull' => false]);
        $table->addColumn('visit_id', 'integer', ['notnull' => false]);
        $table->addColumn('event', 'integer', []);
        $table->addIndex(['visit_id'], 'idx_b39eee8f75fa0ff2', []);
        $table->addUniqueIndex(['web_event_id'], 'uniq_b39eee8f66a8f966');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_tracking_visit_event foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroTrackingVisitEventForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_tracking_visit_event');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_tracking_event'),
            ['web_event_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_tracking_visit'),
            ['visit_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
