<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

/**
 * Defines the contract for providing menu updates for menu items.
 *
 * Implementations of this interface are responsible for retrieving menu update configurations
 * that should be applied to specific menu items. Menu updates allow customization of menu structure,
 * visibility, and properties at runtime without modifying the base menu configuration. This enables
 * administrators to customize menus through the UI while maintaining the original menu definitions.
 */
interface MenuUpdateProviderInterface
{
    /**
     * @param ItemInterface $menuItem
     * @param array         $options
     *
     * @return MenuUpdateInterface[]
     */
    public function getMenuUpdatesForMenuItem(ItemInterface $menuItem, array $options = []);
}
