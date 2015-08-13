<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_5;

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
        $auditTable = $schema->getTable('oro_audit');
        $auditTable->getColumn('user_id')->setNotnull(false);
    }
}
