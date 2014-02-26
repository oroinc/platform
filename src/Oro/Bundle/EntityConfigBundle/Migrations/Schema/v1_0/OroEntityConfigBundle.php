<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroEntityConfigBundle implements Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        // @codingStandardsIgnoreStart
        self::oroEntityConfigTable($schema);
        self::oroEntityConfigFieldTable($schema);
        self::oroEntityConfigLogTable($schema);
        self::oroEntityConfigLogDiffTable($schema);
        self::oroEntityConfigOptionsetTable($schema);
        self::oroEntityConfigOptionsetRelationTable($schema);
        self::oroEntityConfigValueTable($schema);

        self::oroEntityConfigLogDiffForeignKeys($schema);
        self::oroEntityConfigOptionsetForeignKeys($schema);
        self::oroEntityConfigOptionsetRelationForeignKeys($schema);
        self::oroEntityConfigValueForeignKeys($schema);
        self::oroEntityConfigFieldForeignKeys($schema);
        self::oroEntityConfigLogForeignKeys($schema);
        // @codingStandardsIgnoreEnd

        return [];
    }

    /**
     * Generate table oro_entity_config
     *
     * @param Schema $schema
     */
    public static function oroEntityConfigTable(Schema $schema)
    {
        /** Generate table oro_entity_config **/
        $table = $schema->createTable('oro_entity_config');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('class_name', 'string', ['length' => 255]);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('updated', 'datetime', ['notnull' => false]);
        $table->addColumn('mode', 'string', ['length' => 8]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['class_name'], 'oro_entity_config_uq');
        /** End of generate table oro_entity_config **/
    }

    /**
     * Generate table oro_entity_config_field
     *
     * @param Schema $schema
     */
    public static function oroEntityConfigFieldTable(Schema $schema)
    {
        /** Generate table oro_entity_config_field **/
        $table = $schema->createTable('oro_entity_config_field');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('field_name', 'string', ['length' => 255]);
        $table->addColumn('type', 'string', ['length' => 60]);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('updated', 'datetime', ['notnull' => false]);
        $table->addColumn('mode', 'string', ['length' => 8]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_id'], 'IDX_63EC23F781257D5D', []);
        /** End of generate table oro_entity_config_field **/
    }

    /**
     * Generate table oro_entity_config_log
     *
     * @param Schema $schema
     */
    public static function oroEntityConfigLogTable(Schema $schema)
    {
        /** Generate table oro_entity_config_log **/
        $table = $schema->createTable('oro_entity_config_log');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('logged_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_4A4961FBA76ED395', []);
        /** End of generate table oro_entity_config_log **/
    }

    /**
     * Generate table oro_entity_config_log_diff
     *
     * @param Schema $schema
     */
    public static function oroEntityConfigLogDiffTable(Schema $schema)
    {
        /** Generate table oro_entity_config_log_diff **/
        $table = $schema->createTable('oro_entity_config_log_diff');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('log_id', 'integer', ['notnull' => false]);
        $table->addColumn('class_name', 'string', ['length' => 100]);
        $table->addColumn('field_name', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('scope', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('diff', 'text', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['log_id'], 'IDX_D1F6D75AEA675D86', []);
        /** End of generate table oro_entity_config_log_diff **/
    }

    /**
     * Generate table oro_entity_config_optionset
     *
     * @param Schema $schema
     */
    public static function oroEntityConfigOptionsetTable(Schema $schema)
    {
        /** Generate table oro_entity_config_optionset **/
        $table = $schema->createTable('oro_entity_config_optionset');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('field_id', 'integer', ['notnull' => false]);
        $table->addColumn('label', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('priority', 'smallint', ['notnull' => false]);
        $table->addColumn('is_default', 'boolean', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['field_id'], 'IDX_CDC152C4443707B0', []);
        /** End of generate table oro_entity_config_optionset **/
    }

    /**
     * Generate table oro_entity_config_optionset_relation
     *
     * @param Schema $schema
     * @param string $tableName
     */
    public static function oroEntityConfigOptionsetRelationTable(Schema $schema, $tableName = '')
    {
        /** Generate table oro_entity_config_optionset_relation **/
        $table = $schema->createTable($tableName ? : 'oro_entity_config_optionset_relation');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('option_id', 'integer', ['notnull' => false]);
        $table->addColumn('field_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_id', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['field_id'], 'IDX_797D3D83443707B0', []);
        $table->addIndex(['option_id'], 'IDX_797D3D83A7C41D6F', []);
        /** End of generate table oro_entity_config_optionset_relation **/
    }

    /**
     * Generate table oro_entity_config_value
     *
     * @param Schema $schema
     */
    public static function oroEntityConfigValueTable(Schema $schema)
    {
        /** Generate table oro_entity_config_value **/
        $table = $schema->createTable('oro_entity_config_value');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('field_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('scope', 'string', ['length' => 255]);
        $table->addColumn('value', 'text', ['notnull' => false]);
        $table->addColumn('serializable', 'boolean', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_id'], 'IDX_256E3E9B81257D5D', []);
        $table->addIndex(['field_id'], 'IDX_256E3E9B443707B0', []);
        /** End of generate table oro_entity_config_value **/
    }

    /**
     * Generate foreign keys for table oro_entity_config_log_diff
     *
     * @param Schema $schema
     */
    public static function oroEntityConfigLogDiffForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_entity_config_log_diff **/
        $table = $schema->getTable('oro_entity_config_log_diff');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_config_log'),
            ['log_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_entity_config_log_diff **/
    }

    /**
     * Generate foreign keys for table oro_entity_config_optionset
     *
     * @param Schema $schema
     */
    public static function oroEntityConfigOptionsetForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_entity_config_optionset **/
        $table = $schema->getTable('oro_entity_config_optionset');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_config_field'),
            ['field_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_entity_config_optionset **/
    }

    /**
     * Generate foreign keys for table oro_entity_config_optionset_relation
     *
     * @param Schema $schema
     * @param string $tableName
     */
    public static function oroEntityConfigOptionsetRelationForeignKeys(Schema $schema, $tableName = '')
    {
        /** Generate foreign keys for table oro_entity_config_optionset_relation **/
        $table = $schema->getTable($tableName ? : 'oro_entity_config_optionset_relation');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_config_optionset'),
            ['option_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_config_field'),
            ['field_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_entity_config_optionset_relation **/
    }

    /**
     * Generate foreign keys for table oro_entity_config_value
     *
     * @param Schema $schema
     */
    public static function oroEntityConfigValueForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_entity_config_value **/
        $table = $schema->getTable('oro_entity_config_value');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_config_field'),
            ['field_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_config'),
            ['entity_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_entity_config_value **/
    }

    /**
     * Generate foreign keys for table oro_entity_config_field
     *
     * @param Schema $schema
     */
    public static function oroEntityConfigFieldForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_entity_config_field **/
        $table = $schema->getTable('oro_entity_config_field');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_config'),
            ['entity_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_entity_config_field **/
    }

    /**
     * Generate foreign keys for table oro_entity_config_log
     *
     * @param Schema $schema
     */
    public static function oroEntityConfigLogForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_entity_config_log **/
        $table = $schema->getTable('oro_entity_config_log');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_entity_config_log **/
    }
}
