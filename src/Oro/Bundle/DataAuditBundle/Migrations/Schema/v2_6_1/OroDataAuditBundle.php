<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v2_6_1;

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
        $table = $schema->getTable('oro_audit');
        $table->addColumn('entity_id', 'string', ['length' => 255, 'notnull' => false]);
        $table->dropIndex('idx_oro_audit_version');
        $table->addUniqueIndex(['object_id', 'entity_id', 'object_class', 'version'], 'idx_oro_audit_version');
        $table->addIndex(['entity_id', 'object_class', 'type'], 'idx_oro_audit_ent_by_type', []);

        $queries->addPostQuery('UPDATE oro_audit SET entity_id = object_id WHERE entity_id IS NULL');
    }
}
