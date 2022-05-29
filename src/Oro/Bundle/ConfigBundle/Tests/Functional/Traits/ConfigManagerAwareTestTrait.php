<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Traits;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

trait ConfigManagerAwareTestTrait
{
    /**
     * @param string|null $scope The configuration scope (e.g.: global, organization, user, etc.)
     *                           or NULL to get the configuration manager for the current scope
     *
     * @return ConfigManager
     */
    protected static function getConfigManager(?string $scope = 'global'): ConfigManager
    {
        if (!$scope) {
            return self::getContainer()->get('oro_config.manager');
        }

        return self::getContainer()->get('oro_config.' . $scope);
    }
}
