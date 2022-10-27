<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\InstallerBundle\PlatformUpdateCheckerInterface;

/**
 * Checks if the entities configuration has not applied schema changes.
 */
class ExtendEntityPlatformUpdateChecker implements PlatformUpdateCheckerInterface
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function checkReadyToUpdate(): ?array
    {
        $entityClasses = [];
        $configs = $this->configManager->getConfigs('extend');
        foreach ($configs as $config) {
            if ($this->isSchemaUpdateRequired($config)) {
                $entityClasses[] = $config->getId()->getClassName();
            }
        }

        if (!$entityClasses) {
            return null;
        }

        sort($entityClasses);

        return [
            'The entities configuration has not applied schema changes for the following entities: '
            . implode(', ', $entityClasses)
            . '. Please update schema using "oro:entity-extend:update" CLI command (--dry-run option is available).'
            . ' Please note, that schema depends on source code and you may need to rollback to previous version'
            . ' of the source code.'
        ];
    }

    private function isSchemaUpdateRequired(ConfigInterface $config): bool
    {
        //Extend Custom New entity do NOT require update
        if ($config->is('is_extend')
            && $config->is('owner', ExtendScope::OWNER_CUSTOM)
            && $config->is('state', ExtendScope::STATE_NEW)
        ) {
            return false;
        }

        return
            $config->is('is_extend')
            && !$config->is('state', ExtendScope::STATE_ACTIVE)
            && !$config->is('is_deleted');
    }
}
