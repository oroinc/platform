<?php

namespace Oro\Bundle\DataAuditBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

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
