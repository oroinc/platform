<?php

namespace Oro\Bundle\TrackingBundle\Migrations\Schema\v1_0;

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
        /** Generate table oro_tracking_data **/
        $table = $schema->createTable('oro_tracking_data');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('event_id', 'integer', ['notnull' => false]);
        $table->addColumn('data', 'text', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['event_id'], 'UNIQ_B3CFDD2D71F7E88B');
        /** End of generate table oro_tracking_data **/

        /** Generate table oro_tracking_event **/
        $table = $schema->createTable('oro_tracking_event');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('value', 'float', ['notnull' => false]);
        $table->addColumn('user', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('logged_at', 'datetime', []);
        $table->addColumn('url', 'string', ['length' => 255]);
        $table->addColumn('title', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['website_id'], 'IDX_AAD45A1E18F45C82', []);
        $table->addIndex(['name'], 'event_name_idx', []);
        $table->addIndex(['logged_at'], 'event_loggedAt_idx', []);
        /** End of generate table oro_tracking_event **/

        /** Generate table oro_tracking_website **/
        $table = $schema->createTable('oro_tracking_website');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('identifier', 'string', ['length' => 255]);
        $table->addColumn('url', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['identifier'], 'UNIQ_19038898772E836A');
        $table->addIndex(['user_owner_id'], 'IDX_190388989EB185F9', []);
        /** End of generate table oro_tracking_website **/

        /** Generate foreign keys for table oro_tracking_data **/
        $table = $schema->getTable('oro_tracking_data');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_tracking_event'),
            ['event_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_tracking_data **/

        /** Generate foreign keys for table oro_tracking_event **/
        $table = $schema->getTable('oro_tracking_event');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_tracking_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_tracking_event **/

        /** Generate foreign keys for table oro_tracking_website **/
        $table = $schema->getTable('oro_tracking_website');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_tracking_website **/
    }
}
