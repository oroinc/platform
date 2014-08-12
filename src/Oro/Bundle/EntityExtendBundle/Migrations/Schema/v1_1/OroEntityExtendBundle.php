<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEntityExtendBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::oroEnumTable($schema);
        self::oroEnumTransTable($schema);
        self::oroEnumValueTransTable($schema);
    }

    /**
     * Generate table oro_enum
     *
     * @param Schema $schema
     */
    public static function oroEnumTable(Schema $schema)
    {
        /** Generate table oro_enum **/
        $table = $schema->createTable('oro_enum');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['length' => 21]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('public', 'boolean', ['default' => '0']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'oro_enum_uq');
        /** End of generate table oro_enum **/
    }

    /**
     * Generate table oro_enum_trans
     *
     * @param Schema $schema
     */
    public static function oroEnumTransTable(Schema $schema)
    {
        /** Generate table oro_enum_trans **/
        $table = $schema->createTable('oro_enum_trans');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('object_id', 'integer', ['notnull' => false]);
        $table->addColumn('locale', 'string', ['length' => 8]);
        $table->addColumn('field', 'string', ['length' => 32]);
        $table->addColumn('content', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['object_id'], 'IDX_86BF251D232D562B', []);
        $table->addIndex(['locale', 'object_id', 'field'], 'oro_enum_trans_idx', []);
        /** End of generate table oro_enum_trans **/

        /** Generate foreign keys for table oro_enum_trans **/
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_enum'),
            ['object_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null, ]
        );
        /** End of generate foreign keys for table oro_enum_trans **/
    }

    /**
     * Generate table oro_enum_value_trans
     *
     * @param Schema $schema
     */
    public static function oroEnumValueTransTable(Schema $schema)
    {
        /** Generate table oro_enum_value_trans **/
        $table = $schema->createTable('oro_enum_value_trans');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('foreign_key', 'string', ['length' => 32]);
        $table->addColumn('content', 'string', ['length' => 255]);
        $table->addColumn('locale', 'string', ['length' => 8]);
        $table->addColumn('object_class', 'string', ['length' => 255]);
        $table->addColumn('field', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['locale', 'object_class', 'field', 'foreign_key'], 'oro_enum_value_trans_idx', []);
        /** End of generate table oro_enum_value_trans **/

    }
}
