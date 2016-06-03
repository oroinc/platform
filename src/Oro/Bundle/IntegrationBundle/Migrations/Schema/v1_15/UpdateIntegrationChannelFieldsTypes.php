<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityBundle\DBAL\Types\ConfigType;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateIntegrationChannelSettingFieldsTypes implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery(new UpdateIntegrationChannelSettingFieldsValue());
        $configType = Type::getType(ConfigType::TYPE);
        $table = $schema->getTable('oro_integration_channel');

        $table->changeColumn('synchronization_settings', [
            'type' => $configType,
            'comment' => '(DC2Type:config_type)'
        ]);

        $table->changeColumn('mapping_settings', [
            'type' => $configType,
            'comment' => '(DC2Type:config_type)'
        ]);
    }
}
