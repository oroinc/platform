<?php

namespace Oro\Bundle\NavigationBundle\Utils;

use Knp\Menu\ItemInterface;
use Knp\Menu\Iterator\RecursiveItemIterator;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
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

        if ($menuItem->getName() === $name) {
            return $menuItem;
        }

        $item = $menuItem->getChild($name);
        if (!$item) {
            foreach ($menuItem->getChildren() as $child) {
                $item = self::findMenuItem($child, $name);
                if ($item !== null) {
                    break;
                }
            }
        }

        return $item;
    }

    /**
     * @param ItemInterface $menuItem
     * @return iterable<ItemInterface>
     */
    public static function createRecursiveIterator(ItemInterface $menuItem): iterable
    {
        return new \RecursiveIteratorIterator(
            new RecursiveItemIterator($menuItem),
            \RecursiveIteratorIterator::SELF_FIRST
        );
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
        $menuItemsByName = [$menuItem->getName() => $menuItem];
        foreach (self::createRecursiveIterator($menuItem) as $eachName => $eachMenuItem) {
            $menuItemsByName[$eachName] = $eachMenuItem;
        }

        return $menuItemsByName;
    }

    /**
     * Returns true if a custom menu item was created in a scope other than the specified one.
     *
     * When the menu item comes from the menu built for the scope's context, the other scope is always
     * a less specific one, e.g. the organization scope for a website scope.
     */
    public static function isCreatedInOtherScope(?ItemInterface $menuItem, Scope $scope): bool
    {
        if (null === $menuItem || !$menuItem->getExtra(MenuUpdateInterface::IS_CUSTOM)) {
            return false;
        }

        $originScopeId = $menuItem->getExtra(MenuUpdateInterface::ORIGIN_SCOPE_ID);

        return null !== $originScopeId && $scope->getId() !== $originScopeId;
    }

    /**
     * Generates cache key for menu updates in the specified scope.
     */
    public static function generateKey(string $menuName, Scope $scope): string
    {
        return $menuName . '_' . $scope->getId();
    }
}
