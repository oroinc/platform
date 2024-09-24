<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroDataAuditBundle implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_audit');
        $table->addIndex(['logged_at'], 'idx_oro_audit_logged_at', []);
    }
}
