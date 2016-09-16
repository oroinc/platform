<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCollectionTypeColumn implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $auditFieldTable = $schema->getTable('oro_audit_field');
        $auditFieldTable->addColumn('collection_diffs', 'json_array', [
            'notnull' => false,
            'comment' => '(DC2Type:json_array)',
        ]);
    }
}
