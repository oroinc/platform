<?php

namespace Oro\Bundle\TagBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;

class OroTagBundle implements Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_tag_tag **/
        $table = $schema->createTable('oro_tag_tag');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 50]);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('updated', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name'], 'UNIQ_CAF0DB575E237E06');
        $table->addIndex(['user_owner_id'], 'IDX_CAF0DB579EB185F9', []);
        /** End of generate table oro_tag_tag **/

        /** Generate table oro_tag_tagging **/
        $table = $schema->createTable('oro_tag_tagging');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('tag_id', 'integer', ['notnull' => false]);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('alias', 'string', ['length' => 100]);
        $table->addColumn('entity_name', 'string', ['length' => 100]);
        $table->addColumn('record_id', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['tag_id', 'entity_name', 'record_id', 'user_owner_id'], 'tagging_idx');
        $table->addIndex(['tag_id'], 'IDX_50107502BAD26311', []);
        $table->addIndex(['user_owner_id'], 'IDX_501075029EB185F9', []);
        /** End of generate table oro_tag_tagging **/

        /** Generate foreign keys for table oro_tag_tag **/
        $table = $schema->getTable('oro_tag_tag');
        $table->addForeignKeyConstraint($schema->getTable('oro_user'), ['user_owner_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_tag_tag **/

        /** Generate foreign keys for table oro_tag_tagging **/
        $table = $schema->getTable('oro_tag_tagging');
        $table->addForeignKeyConstraint($schema->getTable('oro_user'), ['user_owner_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_tag_tag'), ['tag_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_tag_tagging **/

        // @codingStandardsIgnoreEnd

        return [];
    }
}
