<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Oro\Bundle\NavigationBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;

/**
 * Provides names of menus filtered by the specified scope type.
 */
class MenuNamesProvider
{
    private ConfigurationProvider $configurationProvider;

    public function __construct(ConfigurationProvider $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * @param string $scopeType Scope type to filter menus by, e.g. ConfigurationBuilder::DEFAULT_SCOPE_TYPE.
     *                          Use empty string to get menus from all scope types.
     *
     * @return string[] Menu names.
     */
    public function getMenuNames(string $scopeType = ''): array
    {
        $names = [];
        $menuTree = $this->configurationProvider->getMenuTree();
        foreach ($menuTree as $name => $menuConfig) {
            $menuScopeType = $menuConfig['scope_type'] ?? ConfigurationBuilder::DEFAULT_SCOPE_TYPE;
            if ($scopeType === '' || $menuScopeType === $scopeType) {
                $names[] = $name;
            }
        }

        return $names;
    }
}
