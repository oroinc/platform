<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_6;

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
        $auditTable = $schema->getTable('oro_audit');
        $auditTable->addColumn('type', 'string', ['length' => 255]);
        $auditTable->addIndex(['type'], 'idx_oro_audit_type');

        $auditFieldTable = $schema->getTable('oro_audit_field');
        $auditFieldTable->addColumn('type', 'string', ['length' => 255]);
        $auditFieldTable->addIndex(['type'], 'idx_oro_audit_field_type');
    }
}
