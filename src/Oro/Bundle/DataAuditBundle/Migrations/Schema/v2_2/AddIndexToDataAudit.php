<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v2_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddIndexToDataAudit implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_audit');
        if (!$table->hasIndex('idx_oro_audit_obj_by_type')) {
            $table->addIndex(['object_id', 'object_class', 'type'], 'idx_oro_audit_obj_by_type', []);
        }
    }
}
