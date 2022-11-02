<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v2_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Handles all migrations logic executed during an update
 */
class OroDataAuditBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_audit');
        if (!$table->hasIndex('idx_oro_audit_owner_descr')) {
            $table->addIndex(['owner_description'], 'idx_oro_audit_owner_descr', []);
        }
    }
}
