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
        $auditFieldTable->addColumn('visible', 'boolean', ['default' => '1']);
        $auditFieldTable->addColumn('old_array', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $auditFieldTable->addColumn('new_array', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $auditFieldTable->addColumn('old_simplearray', 'simple_array', [
            'notnull' => false,
            'comment' => '(DC2Type:simple_array)'
        ]);
        $auditFieldTable->addColumn('new_simplearray', 'simple_array', [
            'notnull' => false,
            'comment' => '(DC2Type:simple_array)'
        ]);
        $auditFieldTable->addColumn('old_jsonarray', 'json_array', [
            'notnull' => false,
            'comment' => '(DC2Type:json_array)',
        ]);
        $auditFieldTable->addColumn('new_jsonarray', 'json_array', [
            'notnull' => false,
            'comment' => '(DC2Type:json_array)',
        ]);
    }
}
