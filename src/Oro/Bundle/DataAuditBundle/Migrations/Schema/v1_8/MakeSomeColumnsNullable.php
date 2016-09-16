<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MakeSomeColumnsNullable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $auditTable = $schema->getTable('oro_audit');
        $auditTable->changeColumn('version', ['notnull' => false]);
        $auditTable->changeColumn('action', ['notnull' => false]);
        $auditTable->changeColumn('object_name', ['notnull' => false]);
        $auditTable->changeColumn('logged_at', ['notnull' => false]);
    }
}
