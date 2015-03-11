<?php

namespace Oro\Bundle\TrackingBundle\Migrations\Schema\v1_5;

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
        /** Tables generation **/
        $this->createOroTrackingVisitTable($schema);
        $this->createOroTrackingVisitEventTable($schema);
        $this->createOroTrackingEventDictionaryTable($schema);

        $this->updateOroTrackingEventTable($schema);

        /** Foreign keys generation **/
        $this->addOroTrackingVisitEventForeignKeys($schema);
        $this->addOroTrackingEventDictionaryForeignKeys($schema);
        $this->addOroTrackingVisitForeignKeys($schema);
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
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('visitor_uid', 'string', ['length' => 255]);
        $table->addColumn('ip', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('user_identifier', 'string', ['length' => 255]);
        $table->addColumn('first_action_time', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('last_action_time', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('parsing_count', 'integer', ['default' => '0']);
        $table->addColumn('parsed_uid', 'integer', ['default' => '0']);
        $table->addColumn('identifier_detected', 'boolean', ['default' => '0']);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['website_id'], 'idx_d204b98018f45c82', []);
        $table->addIndex(['visitor_uid'], 'visit_visitorUid_idx', []);
        $table->addIndex(['user_identifier'], 'visit_userIdentifier_idx', []);
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
        $table->addColumn('event_id', 'integer', ['notnull' => false]);
        $table->addColumn('visit_id', 'integer', ['notnull' => false]);
        $table->addColumn('web_event_id', 'integer', ['notnull' => false]);
        $table->addIndex(['event_id'], 'idx_b39eee8f71f7e88b', []);
        $table->addIndex(['visit_id'], 'idx_b39eee8f75fa0ff2', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['web_event_id'], 'uniq_b39eee8f66a8f966');
    }

    /**
     * Add parsed column to the oro_tracking_event table
     *
     * @param Schema $schema
     */
    protected function updateOroTrackingEventTable(Schema $schema)
    {
        $table = $schema->getTable('oro_tracking_event');
        $table->addColumn('parsed', 'boolean', ['default' => '0']);
        $table->addIndex(['created_at'], 'event_createdAt_idx', []);
        $table->addIndex(['parsed'], 'event_parsed_idx', []);
    }

    /**
     * Create oro_tracking_event_lib table
     *
     * @param Schema $schema
     */
    protected function createOroTrackingEventDictionaryTable(Schema $schema)
    {
        $table = $schema->createTable('oro_tracking_event_dictionary');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['website_id'], 'idx_5c3b076318f45c82', []);
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
            $schema->getTable('oro_tracking_event_dictionary'),
            ['event_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_tracking_visit'),
            ['visit_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_tracking_event'),
            ['web_event_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }

    /**
     * Add oro_tracking_event_dictionary foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroTrackingEventDictionaryForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_tracking_event_dictionary');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_tracking_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_tracking_visit foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroTrackingVisitForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_tracking_visit');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_tracking_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
