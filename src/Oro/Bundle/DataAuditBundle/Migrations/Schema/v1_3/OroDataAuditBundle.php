<?php

namespace Oro\Bundle\DataAuditBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
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
        $oroAuditFieldTable->setPrimaryKey(['id']);
        $oroAuditFieldTable->addIndex(['audit_id'], 'IDX_9A31A824BD29F359', []);

        $oroAuditFieldTable->addForeignKeyConstraint(
            $schema->getTable('oro_audit'),
            ['audit_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
