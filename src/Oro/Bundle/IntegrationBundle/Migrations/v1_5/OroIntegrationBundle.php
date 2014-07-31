<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroIntegrationBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_integration_channel');
        $table->changeColumn('id', ['type' => Type::getType('integer')]);
        $table->changeColumn('transport_id', ['type' => Type::getType('integer')]);

        $table = $schema->getTable('oro_integration_channel_status');
        $table->changeColumn('id', ['type' => Type::getType('integer')]);
        $table->changeColumn('channel_id', ['type' => Type::getType('integer')]);

        $table = $schema->getTable('oro_integration_transport');
        $table->changeColumn('id', ['type' => Type::getType('integer')]);
    }
}
