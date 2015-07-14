<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_4;

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
        $auditFieldTable = $schema->getTable('oro_audit_field');
        if ($auditFieldTable->hasColumn('old_datetimetz')) {
            return; // all columns were added in version 1.3
        }

        $auditFieldTable->addColumn('old_datetimetz', 'datetimetz', ['notnull' => false]);
        $auditFieldTable->addColumn('old_object', 'object', ['notnull' => false, 'comment' => '(DC2Type:object)']);
        $auditFieldTable->addColumn('new_datetimetz', 'datetimetz', ['notnull' => false]);
        $auditFieldTable->addColumn('new_object', 'object', ['notnull' => false, 'comment' => '(DC2Type:object)']);
    }
}
