<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateGroupNameUniqueIndex implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_access_group');
        $table->dropIndex('UNIQ_FEF9EDB75E237E06');
        $table->addUniqueIndex(['name', 'organization_id'], 'uq_name_org_idx');
    }
}
