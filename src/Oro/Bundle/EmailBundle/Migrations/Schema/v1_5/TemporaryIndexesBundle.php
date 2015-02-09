<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_5;

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
            $table->addIndex(['related_activity_class'], 'tmp1');
            $table->addIndex(['related_activity_id'], 'tmp2');

            $queries->addQuery(new UpdateDateActivityListQuery());
        } catch (SchemaException $e) {
        }
    }
}
