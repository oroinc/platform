<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v2_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add unique index for transaction field.
 */
class AddUniqIndexForTransactionField implements
    Migration,
    OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addPreQuery(new RemoveAuditDuplicatesQuery());

        $table = $schema->getTable('oro_audit');
        if (!$table->hasIndex('idx_oro_audit_transaction')) {
            $table->addUniqueIndex([
                'object_id',
                'object_class',
                'transaction_id',
                'type'
            ], 'idx_oro_audit_transaction');
        }

        // Update version unique index including type discriminator field
        $table->dropIndex('idx_oro_audit_version');
        $table->addUniqueIndex(
            ['object_id', 'object_class', 'version', 'type'],
            'idx_oro_audit_version'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder(): int
    {
        return 2;
    }
}
