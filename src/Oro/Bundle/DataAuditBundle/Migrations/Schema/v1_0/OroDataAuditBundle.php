<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;

class OroDataAuditBundle implements Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_audit **/
        $table = $schema->createTable('oro_audit');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('action', 'string', ['length' => 8]);
        $table->addColumn('logged_at', 'datetime', []);
        $table->addColumn('object_id', 'integer', ['notnull' => false]);
        $table->addColumn('object_class', 'string', ['length' => 255]);
        $table->addColumn('object_name', 'string', ['length' => 255]);
        $table->addColumn('version', 'integer', []);
        $table->addColumn('data', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_5FBA427CA76ED395', []);
        /** End of generate table oro_audit **/

        /** Generate foreign keys for table oro_audit **/
        $table = $schema->getTable('oro_audit');
        $table->addForeignKeyConstraint($schema->getTable('oro_user'), ['user_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_audit **/

        // @codingStandardsIgnoreEnd

        return [];
    }
}
