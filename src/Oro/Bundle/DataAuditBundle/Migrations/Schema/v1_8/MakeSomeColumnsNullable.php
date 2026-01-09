<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MakeSomeColumnsNullable implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $auditTable = $schema->getTable('oro_audit');
        $auditTable->modifyColumn('version', ['notnull' => false]);
        $auditTable->modifyColumn('action', ['notnull' => false]);
        $auditTable->modifyColumn('object_name', ['notnull' => false]);
        $auditTable->modifyColumn('logged_at', ['notnull' => false]);
    }
}
