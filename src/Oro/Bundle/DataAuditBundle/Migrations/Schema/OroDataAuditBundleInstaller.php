<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_9\AddImpersonationColumn;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Handles all migrations logic executed during installation
 */
class OroDataAuditBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v2_6';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createAudit($schema);
        $this->createAuditField($schema);

        AddImpersonationColumn::addImpersonationColumn($schema);
    }

    /**
     * @param Schema $schema
     */
    private function createAudit(Schema $schema)
    {
        $auditTable = $schema->createTable('oro_audit');
        $auditTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $auditTable->addColumn('user_id', 'integer', ['notnull' => false]);
        $auditTable->addColumn('action', 'string', ['length' => 8, 'notnull' => false]);
        $auditTable->addColumn('logged_at', 'datetime', ['notnull' => false]);
        $auditTable->addColumn('object_id', 'integer', ['notnull' => false]);
        $auditTable->addColumn('object_class', 'string', ['length' => 255]);
        $auditTable->addColumn(
            'object_name',
            'string',
            ['length' => AbstractAudit::OBJECT_NAME_MAX_LENGTH, 'notnull' => false]
        );
        $auditTable->addColumn('version', 'integer', ['notnull' => false]);
        $auditTable->addColumn('organization_id', 'integer', ['notnull' => false]);
        $auditTable->addColumn('type', 'string', ['length' => 255]);
        $auditTable->addColumn('transaction_id', 'string', ['length' => 255]);
        $auditTable->addColumn('owner_description', 'string', ['notnull' => false, 'length' => 255]);
        $auditTable->addColumn('additional_fields', 'array', ['notnull' => false]);

        $auditTable->setPrimaryKey(['id']);

        $auditTable->addIndex(['user_id'], 'IDX_5FBA427CA76ED395', []);
        $auditTable->addIndex(['type'], 'idx_oro_audit_type');
        $auditTable->addUniqueIndex(['object_id', 'object_class', 'version'], 'idx_oro_audit_version');

        $auditTable->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $auditTable->addIndex(['logged_at'], 'idx_oro_audit_logged_at', []);
        $auditTable->addIndex(['object_class'], 'idx_oro_audit_object_class', []);
        $auditTable->addIndex(['object_id', 'object_class', 'type'], 'idx_oro_audit_obj_by_type', []);
        $auditTable->addIndex(['owner_description'], 'idx_oro_audit_owner_descr', []);

        $auditTable->addIndex(['organization_id'], 'idx_oro_audit_organization_id', []);
        $auditTable->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }

    /**
     * @param Schema $schema
     */
    private function createAuditField(Schema $schema)
    {
        $auditFieldTable = $schema->createTable('oro_audit_field');
        $auditFieldTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $auditFieldTable->addColumn('audit_id', 'integer', []);
        $auditFieldTable->addColumn('field', 'string', ['length' => 255]);
        $auditFieldTable->addColumn('data_type', 'string', ['length' => 255]);
        $auditFieldTable->addColumn('old_integer', 'bigint', ['notnull' => false]);
        $auditFieldTable->addColumn('old_float', 'float', ['notnull' => false]);
        $auditFieldTable->addColumn('old_boolean', 'boolean', ['notnull' => false]);
        $auditFieldTable->addColumn('old_text', 'text', ['notnull' => false]);
        $auditFieldTable->addColumn('old_date', 'date', ['notnull' => false]);
        $auditFieldTable->addColumn('old_time', 'time', ['notnull' => false]);
        $auditFieldTable->addColumn('old_datetime', 'datetime', ['notnull' => false]);
        $auditFieldTable->addColumn('new_integer', 'bigint', ['notnull' => false]);
        $auditFieldTable->addColumn('new_float', 'float', ['notnull' => false]);
        $auditFieldTable->addColumn('new_boolean', 'boolean', ['notnull' => false]);
        $auditFieldTable->addColumn('new_text', 'text', ['notnull' => false]);
        $auditFieldTable->addColumn('new_date', 'date', ['notnull' => false]);
        $auditFieldTable->addColumn('new_time', 'time', ['notnull' => false]);
        $auditFieldTable->addColumn('new_datetime', 'datetime', ['notnull' => false]);
        $auditFieldTable->addColumn(
            'old_datetimetz',
            'datetimetz',
            [
                'notnull' => false,
                'comment' => '(DC2Type:datetimetz)',
            ]
        );
        $auditFieldTable->addColumn('old_object', 'object', ['notnull' => false, 'comment' => '(DC2Type:object)']);
        $auditFieldTable->addColumn(
            'new_datetimetz',
            'datetimetz',
            [
                'notnull' => false,
                'comment' => '(DC2Type:datetimetz)',
            ]
        );
        $auditFieldTable->addColumn('new_object', 'object', ['notnull' => false, 'comment' => '(DC2Type:object)']);
        $auditFieldTable->addColumn('visible', 'boolean', ['default' => '1']);
        $auditFieldTable->addColumn('old_array', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $auditFieldTable->addColumn('new_array', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $auditFieldTable->addColumn(
            'old_simplearray',
            'simple_array',
            [
                'notnull' => false,
                'comment' => '(DC2Type:simple_array)',
            ]
        );
        $auditFieldTable->addColumn(
            'new_simplearray',
            'simple_array',
            [
                'notnull' => false,
                'comment' => '(DC2Type:simple_array)',
            ]
        );
        $auditFieldTable->addColumn(
            'old_jsonarray',
            'json_array',
            [
                'notnull' => false,
                'comment' => '(DC2Type:json_array)',
            ]
        );
        $auditFieldTable->addColumn(
            'new_jsonarray',
            'json_array',
            [
                'notnull' => false,
                'comment' => '(DC2Type:json_array)',
            ]
        );
        $auditFieldTable->addColumn(
            'collection_diffs',
            'json_array',
            [
                'notnull' => false,
                'comment' => '(DC2Type:json_array)',
            ]
        );
        $auditFieldTable->addColumn('translation_domain', 'string', ['length' => 100, 'notnull' => false]);
        $auditFieldTable->setPrimaryKey(['id']);
        $auditFieldTable->addIndex(['audit_id'], 'IDX_9A31A824BD29F359', []);

        $auditFieldTable->addForeignKeyConstraint(
            $schema->getTable('oro_audit'),
            ['audit_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
