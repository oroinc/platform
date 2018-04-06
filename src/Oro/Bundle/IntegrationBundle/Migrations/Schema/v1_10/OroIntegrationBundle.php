<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroIntegrationBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_integration_channel_status');
        $table->addIndex(['date'], 'oro_intch_date_idx', []);
        $table->addIndex(['connector', 'code'], 'oro_intch_con_state_idx', []);
    }
}
