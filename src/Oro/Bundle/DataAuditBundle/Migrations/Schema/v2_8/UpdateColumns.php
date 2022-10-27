<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v2_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Changed length for type and transaction_id fields for oro_audit table
 */
class UpdateColumns implements
    Migration,
    OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $auditTable = $schema->getTable('oro_audit');
        $auditTable->changeColumn('type', ['length' => 30]);
        $auditTable->changeColumn('transaction_id', ['length' => 36]);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder(): int
    {
        return 1;
    }
}
