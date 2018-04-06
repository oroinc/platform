<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroDataAuditBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createAuditField($schema);
        $queries->addPostQuery(new MigrateAuditFieldQuery());
        $queries->addPostQuery('ALTER TABLE oro_audit DROP COLUMN data');
        $queries->addPostQuery(
            $this->getDropEntityConfigFieldQuery('Oro\Bundle\DataAuditBundle\Entity\Audit', 'data')
        );
    }

    /**
     * @param Schema $schema
     */
    private function createAuditField(Schema $schema)
    {
        $oroAuditFieldTable = $schema->createTable('oro_audit_field');
        $oroAuditFieldTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $oroAuditFieldTable->addColumn('audit_id', 'integer', []);
        $oroAuditFieldTable->addColumn('field', 'string', ['length' => 255]);
        $oroAuditFieldTable->addColumn('data_type', 'string', ['length' => 255]);
        $oroAuditFieldTable->addColumn('old_integer', 'bigint', ['notnull' => false]);
        $oroAuditFieldTable->addColumn('old_float', 'float', ['notnull' => false]);
        $oroAuditFieldTable->addColumn('old_boolean', 'boolean', ['notnull' => false]);
        $oroAuditFieldTable->addColumn('old_text', 'text', ['notnull' => false]);
        $oroAuditFieldTable->addColumn('old_date', 'date', ['notnull' => false]);
        $oroAuditFieldTable->addColumn('old_time', 'time', ['notnull' => false]);
        $oroAuditFieldTable->addColumn('old_datetime', 'datetime', ['notnull' => false]);
        $oroAuditFieldTable->addColumn('new_integer', 'bigint', ['notnull' => false]);
        $oroAuditFieldTable->addColumn('new_float', 'float', ['notnull' => false]);
        $oroAuditFieldTable->addColumn('new_boolean', 'boolean', ['notnull' => false]);
        $oroAuditFieldTable->addColumn('new_text', 'text', ['notnull' => false]);
        $oroAuditFieldTable->addColumn('new_date', 'date', ['notnull' => false]);
        $oroAuditFieldTable->addColumn('new_time', 'time', ['notnull' => false]);
        $oroAuditFieldTable->addColumn('new_datetime', 'datetime', ['notnull' => false]);
        $oroAuditFieldTable->addColumn('old_datetimetz', 'datetimetz', ['notnull' => false]);
        $oroAuditFieldTable->addColumn('old_object', 'object', ['notnull' => false, 'comment' => '(DC2Type:object)']);
        $oroAuditFieldTable->addColumn('new_datetimetz', 'datetimetz', ['notnull' => false]);
        $oroAuditFieldTable->addColumn('new_object', 'object', ['notnull' => false, 'comment' => '(DC2Type:object)']);
        $oroAuditFieldTable->addColumn('visible', 'boolean', ['default' => '1']);
        $oroAuditFieldTable->addColumn('old_array', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $oroAuditFieldTable->addColumn('new_array', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $oroAuditFieldTable->addColumn('old_simplearray', 'simple_array', [
            'notnull' => false,
            'comment' => '(DC2Type:simple_array)'
        ]);
        $oroAuditFieldTable->addColumn('new_simplearray', 'simple_array', [
            'notnull' => false,
            'comment' => '(DC2Type:simple_array)'
        ]);
        $oroAuditFieldTable->addColumn('old_jsonarray', 'json_array', [
            'notnull' => false,
            'comment' => '(DC2Type:json_array)',
        ]);
        $oroAuditFieldTable->addColumn('new_jsonarray', 'json_array', [
            'notnull' => false,
            'comment' => '(DC2Type:json_array)',
        ]);
        $oroAuditFieldTable->setPrimaryKey(['id']);
        $oroAuditFieldTable->addIndex(['audit_id'], 'IDX_9A31A824BD29F359', []);

        $oroAuditFieldTable->addForeignKeyConstraint(
            $schema->getTable('oro_audit'),
            ['audit_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param string $className
     * @param string $fieldName
     *
     * @return ParametrizedSqlMigrationQuery
     */
    private function getDropEntityConfigFieldQuery($className, $fieldName)
    {
        $dropFieldIndexSql = 'DELETE FROM oro_entity_config_index_value'
            . ' WHERE entity_id IS NULL AND field_id IN ('
            . ' SELECT oecf.id FROM oro_entity_config_field AS oecf'
            . ' WHERE oecf.field_name = :field'
            . ' AND oecf.entity_id IN ('
            . ' SELECT oec.id'
            . ' FROM oro_entity_config AS oec'
            . ' WHERE oec.class_name = :class'
            . ' ))';
        $dropFieldSql      = 'DELETE FROM oro_entity_config_field'
            . ' WHERE field_name = :field'
            . ' AND entity_id IN ('
            . ' SELECT id'
            . ' FROM oro_entity_config'
            . ' WHERE class_name = :class'
            . ' )';

        $query = new ParametrizedSqlMigrationQuery();
        $query->addSql(
            $dropFieldIndexSql,
            ['field' => $fieldName, 'class' => $className],
            ['field' => 'string', 'class' => 'string']
        );
        $query->addSql(
            $dropFieldSql,
            ['field' => $fieldName, 'class' => $className],
            ['field' => 'string', 'class' => 'string']
        );

        return $query;
    }
}
