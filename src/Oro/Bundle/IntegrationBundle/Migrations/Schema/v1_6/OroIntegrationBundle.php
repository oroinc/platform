<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\EntityBundle\Migrations\MigrateTypeMigration;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroIntegrationBundle extends MigrateTypeMigration implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->changeType($schema, $queries, 'oro_integration_channel', 'id', Type::INTEGER);
        $this->changeType($schema, $queries, 'oro_integration_channel_status', 'id', Type::INTEGER);
        $this->changeType($schema, $queries, 'oro_integration_transport', 'id', Type::INTEGER);
    }
}
