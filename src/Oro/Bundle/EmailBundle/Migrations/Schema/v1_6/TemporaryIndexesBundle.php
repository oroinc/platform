<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class TemporaryIndexesBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        try {
            $table = $schema->getTable('oro_activity_list');
            $table->dropIndex('tmp1');
            $table->dropIndex('tmp2');
        } catch (SchemaException $e) {
        }
    }
}
