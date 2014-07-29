<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroIntegrationBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::modifyChannelStatusTable($schema);
    }

    /**
     * Change oro_integration_channel table
     *
     * @param Schema $schema
     */
    public static function modifyChannelStatusTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_channel_status');
        $table->addColumn('data', Type::JSON_ARRAY, ['notnull' => false]);
    }
}
