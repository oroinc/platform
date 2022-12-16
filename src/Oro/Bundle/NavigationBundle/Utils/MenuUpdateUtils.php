<?php

namespace Oro\Bundle\NavigationBundle\Utils;

use Knp\Menu\ItemInterface;
use Knp\Menu\Iterator\RecursiveItemIterator;
use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;
use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * Contains handy functions for working with menu items.
 */
class MenuUpdateUtils
{
    /**
     * Finds the menu item by its name.
     */
    public static function findMenuItem(ItemInterface $menuItem, ?string $name): ?ItemInterface
    {
        if (null === $name) {
            return null;
        }

        $item = $menuItem->getChild($name);
        if (!$item) {
            foreach ($menuItem->getChildren() as $child) {
                $item = self::findMenuItem($child, $name);
                if ($item instanceof ItemInterface) {
                    break;
                }
            }
        }

        return $item;
    }

    /**
     * Flattens the menu item tree.
     *
     * @param ItemInterface $menuItem
     *
     * @return array<string,ItemInterface> Menu item and its children from all levels.
     *  [
     *      'menu_item_name' => ItemInterface,
     *      // ...
     *  ]
     */
    public static function flattenMenuItem(ItemInterface $menuItem): array
    {
        $menuIterator = new RecursiveItemIterator($menuItem);
        $recursiveMenuIterator = new \RecursiveIteratorIterator($menuIterator, \RecursiveIteratorIterator::SELF_FIRST);
        $menuItemsByName = [$menuItem->getName() => $menuItem];
        foreach ($recursiveMenuIterator as $eachName => $eachMenuItem) {
            $menuItemsByName[$eachName] = $eachMenuItem;
        }

        return $menuItemsByName;
    }

    /**
     * Generates cache key for menu updates in the specified scope.
     */
    public static function generateKey(string $menuName, Scope $scope): string
    {
        return $menuName . '_' . $scope->getId();
    }

    public static function getAllowedNestingLevel(ItemInterface $menuItem): int
    {
        $menu = $menuItem->getRoot();
        $menuMaxNestingLevel = (int)$menu->getExtra(ConfigurationBuilder::MAX_NESTING_LEVEL, 0);
        $level = count(LostItemsManipulator::getParents($menuItem));

        return max(0, $menuMaxNestingLevel - $level);
    }
}
