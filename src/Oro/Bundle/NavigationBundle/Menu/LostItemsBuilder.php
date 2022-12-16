<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\MenuUpdateApplier\MenuUpdateApplier;
use Oro\Bundle\NavigationBundle\Utils\LostItemsManipulator;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;

/**
 * Moves menu items from the lost items container to their implied parents.
 */
class LostItemsBuilder implements BuilderInterface
{
    public function build(ItemInterface $menu, array $options = [], $alias = null): void
    {
        if (!$menu->isDisplayed()) {
            return;
        }

        $lostItemsContainer = LostItemsManipulator::getLostItemsContainer($menu, false);
        if ($lostItemsContainer === null) {
            return;
        }

        // Having a flat array of all menu items simplifies accessing them by name.
        $menuItemsByName = MenuUpdateUtils::flattenMenuItem($menu);

        foreach ($lostItemsContainer->getChildren() as $lostMenuItem) {
            $isCustom = $lostMenuItem->getExtra(MenuUpdateApplier::IS_CUSTOM);
            if (!$isCustom) {
                // Skips non-custom menu items because they should not appear in menu.
                continue;
            }

            $impliedParentKey = $lostMenuItem->getExtra(LostItemsManipulator::IMPLIED_PARENT_NAME);
            $impliedParentMenuItem = $menuItemsByName[$impliedParentKey] ?? $menu;

            $lostMenuItem->getParent()?->removeChild($lostMenuItem->getName());
            $impliedParentMenuItem->addChild($lostMenuItem);
        }

        $menu->removeChild($lostItemsContainer);
    }
}
