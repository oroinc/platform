<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v2_4_1;

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
        $table->addIndex(['owner_description'], 'idx_oro_audit_owner_descr', []);
    }
}
