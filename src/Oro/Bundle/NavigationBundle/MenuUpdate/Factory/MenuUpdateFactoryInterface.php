<?php

namespace Oro\Bundle\NavigationBundle\MenuUpdate\Factory;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * Interface for factory that creates an instance of {@see MenuUpdateInterface}.
 */
interface MenuUpdateFactoryInterface
{
    /**
     * @param string $menuName
     * @param Scope $scope
     * @param array $options Arbitrary options to take into account when creating a menu update.
     *  [
     *      ?'key' => string, // Menu item name to apply the menu update to.
     *      // ... other available fields of the menu update.
     *  ]
     *
     * @return MenuUpdateInterface
     */
    public function createMenuUpdate(string $menuName, Scope $scope, array $options = []): MenuUpdateInterface;
}
