<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Platforms\PostgreSQL92Platform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityBundle\DBAL\Types\ConfigObjectType;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateIntegrationChannelSettingFieldsTypes implements Migration, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery(new UpdateIntegrationChannelSettingFieldsValue());

        if (!$this->platform instanceof PostgreSQL92Platform) {
            $configType = Type::getType(ConfigObjectType::TYPE);
            $table = $schema->getTable('oro_integration_channel');

            $table->modifyColumn('synchronization_settings', [
                'type' => $configType,
                'comment' => '(DC2Type:config_object)'
            ]);

            $table->modifyColumn('mapping_settings', [
                'type' => $configType,
                'comment' => '(DC2Type:config_object)'
            ]);
        }
    }
}
