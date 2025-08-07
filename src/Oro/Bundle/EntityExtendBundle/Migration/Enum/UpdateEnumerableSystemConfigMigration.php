<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Enum;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Psr\Container\ContainerInterface;

/**
 * This migration updates enumerable system config values that contain enum option IDs.
 */
class UpdateEnumerableSystemConfigMigration implements Migration
{
    public function __construct(protected ContainerInterface $container, private iterable $providers)
    {
    }

    public function up(Schema $schema, QueryBag $queries): void
    {
        $updateNeeded = false;
        $configManager = $this->getConfigManager();
        /** @var UpdateEnumOptionIdsConfigKeysProvider $provider */
        foreach ($this->providers as $provider) {
            foreach ($provider->getConfigKeys() as $configKey) {
                $value = $configManager->get($configKey);
                if (!is_array($value)) {
                    continue;
                }
                $firstEnumOption = reset($value);
                if (!ExtendHelper::isInternalEnumId($firstEnumOption)) {
                    continue;
                }
                $updatedValue = ExtendHelper::mapToEnumOptionIds(
                    $provider->getEnumCode(),
                    $value
                );
                $updateNeeded = true;
                $configManager->set($configKey, $updatedValue);
            }
        }
        if ($updateNeeded) {
            $configManager->flush();
        }
    }

    protected function getConfigManager(): ConfigManager
    {
        return $this->container->get('oro_config.global');
    }
}
