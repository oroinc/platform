<?php

namespace Oro\Bundle\DataAuditBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

/**
 * Migration extension for adding custom audit field type columns to the audit field table.
 *
 * This extension provides a convenient way to add new auditable data types to the system by creating
 * the necessary database columns in the `oro_audit_field` table. For each new audit type, it creates
 * both `old_` and `new_` columns to store the previous and current values of audited fields. This
 * allows developers to extend the audit system with custom field types beyond the standard supported
 * types (string, numeric, date, etc.).
 */
class AuditFieldExtension
{
    /**
     * @param Schema $schema
     * @param string $doctrineType
     * @param string $auditType
     */
    public function addType(Schema $schema, $doctrineType, $auditType)
    {
        $auditFieldTable = $schema->getTable('oro_audit_field');

        $auditFieldTable->addColumn(sprintf('old_%s', $auditType), $doctrineType, [
            'oro_options' => [
                'extend' => ['owner' => ExtendScope::OWNER_SYSTEM]
            ],
            'notnull' => false
        ]);
        $auditFieldTable->addColumn(sprintf('new_%s', $auditType), $doctrineType, [
            'oro_options' => [
                'extend' => ['owner' => ExtendScope::OWNER_SYSTEM]
            ],
            'notnull' => false
        ]);
    }
}
