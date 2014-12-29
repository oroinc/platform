<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroIntegrationBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_integration_channel');
        $table->addColumn('is_two_way_sync_enabled', 'boolean', ['notnull' => false]);
        $table->addColumn('sync_priority', 'string', ['notnull' => false, 'length' => 255]);
    }
}
