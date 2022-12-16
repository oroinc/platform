<?php

namespace Oro\Bundle\NavigationBundle\Utils;

use Knp\Menu\ItemInterface;

/**
 * Contains handy functions for working with lost menu items.
 */
class LostItemsManipulator
{
    public const IMPLIED_PARENT_NAME = 'implied_parent_name';
    public const LOST_ITEMS_CONTAINER = '__lost_items__';

    public static function getLostItemsContainer(
        ItemInterface $menuItem,
        bool $createIfNotExists = true
    ): ?ItemInterface {
        $menu = $menuItem->getRoot();
        $lostItemsContainer = $menu->getChild(self::LOST_ITEMS_CONTAINER);
        if ($lostItemsContainer === null && $createIfNotExists) {
            $lostItemsContainer = $menu->addChild(self::LOST_ITEMS_CONTAINER);
        }

        return $lostItemsContainer;
    }

    public static function isLostItemsContainer(ItemInterface $menuItem): bool
    {
        return $menuItem->getName() === self::LOST_ITEMS_CONTAINER;
    }

    public static function getParents(ItemInterface $menuItem): array
    {
        $parents = [];
        while ($parentMenuItem = self::getParent($menuItem)) {
            $parents[$parentMenuItem->getName()] = $parentMenuItem;
            $menuItem = $parentMenuItem;
        }

        return array_reverse($parents);
    }

    public static function getParent(ItemInterface $menuItem): ?ItemInterface
    {
        $parentMenuItem = $menuItem->getParent();
        if (!$parentMenuItem || self::isLostItemsContainer($parentMenuItem)) {
            $impliedParentKey = $menuItem->getExtra(self::IMPLIED_PARENT_NAME);
            if ($impliedParentKey) {
                $parentMenuItem = MenuUpdateUtils::findMenuItem($menuItem->getRoot(), $impliedParentKey);
            }
        }

        return $parentMenuItem;
    }
}
