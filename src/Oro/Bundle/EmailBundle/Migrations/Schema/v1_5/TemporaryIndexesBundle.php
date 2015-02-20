<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class TemporaryIndexesBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema->hasTable('oro_activity_list')) {
            $table = $schema->getTable('oro_activity_list');
            $table->addIndex(['related_activity_class'], 'tmp_al_related_activity_class');
            $table->addIndex(['related_activity_id'], 'tmp_al_related_activity_id');

            $queries->addQuery(new UpdateDateActivityListQuery());
        }
    }
}
