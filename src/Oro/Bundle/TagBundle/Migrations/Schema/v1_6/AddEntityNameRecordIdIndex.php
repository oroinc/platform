<?php

namespace Oro\Bundle\TagBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddEntityNameRecordIdIndex implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_tag_tagging');
        $table->dropIndex('entity_name_idx');
        $table->addIndex(['entity_name', 'record_id'], 'entity_name_idx', []);
    }
}
