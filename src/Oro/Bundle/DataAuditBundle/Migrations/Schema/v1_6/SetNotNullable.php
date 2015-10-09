<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class SetNotNullable implements Migration, OrderedMigrationInterface
{
    /** {@inheritdoc} */
    public function getOrder()
    {
        return 30;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $auditTable = $schema->getTable('oro_audit');
        $auditTable->getColumn('type')->setOptions(['length' => 255]);
        $auditTable->addIndex(['type'], 'idx_oro_audit_type');

        $auditFieldTable = $schema->getTable('oro_audit_field');
        $auditFieldTable->getColumn('type')->setOptions(['length' => 255]);
        $auditFieldTable->addIndex(['type'], 'idx_oro_audit_field_type');
    }
}
