<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroDataAuditBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v2_8';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createAuditTable($schema);
        $this->createAuditFieldTable($schema);

        /** Foreign keys generation **/
        $this->addAuditForeignKeys($schema);
        $this->addAuditFieldForeignKeys($schema);
    }

    private function createAuditTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_audit');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('action', 'string', ['length' => 8, 'notnull' => false]);
        $table->addColumn('logged_at', 'datetime', ['notnull' => false]);
        $table->addColumn('object_id', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('object_class', 'string', ['length' => 255]);
        $table->addColumn('object_name', 'string', [
            'length' => AbstractAudit::OBJECT_NAME_MAX_LENGTH,
            'notnull' => false
        ]);
        $table->addColumn('version', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('type', 'string', ['length' => 30]);
        $table->addColumn('transaction_id', 'string', ['length' => 36]);
        $table->addColumn('owner_description', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('additional_fields', 'array', ['notnull' => false]);
        $table->addColumn('impersonation_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_5FBA427CA76ED395');
        $table->addIndex(['type'], 'idx_oro_audit_type');
        $table->addIndex(['logged_at'], 'idx_oro_audit_logged_at');
        $table->addIndex(['object_class'], 'idx_oro_audit_object_class');
        $table->addIndex(['object_id', 'object_class', 'type'], 'idx_oro_audit_obj_by_type');
        $table->addIndex(['owner_description'], 'idx_oro_audit_owner_descr');
        $table->addIndex(['organization_id'], 'idx_oro_audit_organization_id');
        $table->addUniqueIndex(['object_id', 'object_class', 'version', 'type'], 'idx_oro_audit_version');
        $table->addUniqueIndex(['object_id', 'object_class', 'transaction_id', 'type'], 'idx_oro_audit_transaction');
    }

    private function createAuditFieldTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_audit_field');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('audit_id', 'integer');
        $table->addColumn('field', 'string', ['length' => 255]);
        $table->addColumn('data_type', 'string', ['length' => 255]);
        $table->addColumn('old_integer', 'bigint', ['notnull' => false]);
        $table->addColumn('old_float', 'float', ['notnull' => false]);
        $table->addColumn('old_boolean', 'boolean', ['notnull' => false]);
        $table->addColumn('old_text', 'text', ['notnull' => false]);
        $table->addColumn('old_date', 'date', ['notnull' => false]);
        $table->addColumn('old_time', 'time', ['notnull' => false]);
        $table->addColumn('old_datetime', 'datetime', ['notnull' => false]);
        $table->addColumn('new_integer', 'bigint', ['notnull' => false]);
        $table->addColumn('new_float', 'float', ['notnull' => false]);
        $table->addColumn('new_boolean', 'boolean', ['notnull' => false]);
        $table->addColumn('new_text', 'text', ['notnull' => false]);
        $table->addColumn('new_date', 'date', ['notnull' => false]);
        $table->addColumn('new_time', 'time', ['notnull' => false]);
        $table->addColumn('new_datetime', 'datetime', ['notnull' => false]);
        $table->addColumn('old_datetimetz', 'datetimetz', ['notnull' => false, 'comment' => '(DC2Type:datetimetz)']);
        $table->addColumn('old_object', 'object', ['notnull' => false, 'comment' => '(DC2Type:object)']);
        $table->addColumn('new_datetimetz', 'datetimetz', ['notnull' => false, 'comment' => '(DC2Type:datetimetz)']);
        $table->addColumn('new_object', 'object', ['notnull' => false, 'comment' => '(DC2Type:object)']);
        $table->addColumn('visible', 'boolean', ['default' => '1']);
        $table->addColumn('old_array', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('new_array', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('old_simplearray', 'simple_array', [
            'notnull' => false,
            'comment' => '(DC2Type:simple_array)'
        ]);
        $table->addColumn('new_simplearray', 'simple_array', [
            'notnull' => false,
            'comment' => '(DC2Type:simple_array)',
        ]);
        $table->addColumn('old_jsonarray', 'json_array', [
            'notnull' => false,
            'comment' => '(DC2Type:json_array)',
        ]);
        $table->addColumn('new_jsonarray', 'json_array', [
            'notnull' => false,
            'comment' => '(DC2Type:json_array)',
        ]);
        $table->addColumn('collection_diffs', 'json_array', [
            'notnull' => false,
            'comment' => '(DC2Type:json_array)',
        ]);
        $table->addColumn('translation_domain', 'string', ['length' => 100, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['audit_id'], 'IDX_9A31A824BD29F359');
    }

    private function addAuditForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_audit');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user_impersonation'),
            ['impersonation_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }

    private function addAuditFieldForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_audit_field');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_audit'),
            ['audit_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
