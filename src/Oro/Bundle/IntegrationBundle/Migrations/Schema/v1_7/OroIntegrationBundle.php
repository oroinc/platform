<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroIntegrationBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::modifyChannelTable($schema);
    }

    /**
     * Change oro_integration_channel table
     *
     * @param Schema $schema
     */
    public static function modifyChannelTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_channel');
        $table->addColumn('edit_mode', 'integer', ['notnull' => true, 'default' => Channel::EDIT_MODE_ALLOW]);
    }
}
