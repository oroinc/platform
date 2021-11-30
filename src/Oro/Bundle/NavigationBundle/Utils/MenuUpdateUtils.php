<?php

namespace Oro\Bundle\NavigationBundle\Utils;

use Knp\Menu\ItemInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * Helps to update MenuItem object using MenuUpdate entity data and find menu items
 */
class MenuUpdateUtils
{
    /**
     * Apply changes from menu update to menu item
     */
    public static function updateMenuItem(
        MenuUpdateInterface $update,
        ItemInterface $menu,
        LocalizationHelper $localizationHelper,
        array $options = []
    ) {
        $item = self::findOrCreateMenuItem($update, $menu, $options);
        if ($item === null) {
            return;
        }

        if ($update->getTitles()->count()) {
            $item->setLabel((string) $update->getTitle($localizationHelper->getCurrentLocalization()));
        }

        if ($update->getUri()) {
            $item->setUri($update->getUri());
        }

        $item->setDisplay($update->isActive());

        foreach ($update->getExtras() as $key => $extra) {
            $item->setExtra($key, $extra);
        }

        foreach ($update->getLinkAttributes() as $key => $linkAttribute) {
            $item->setLinkAttribute($key, $linkAttribute);
        }

        if ($update->getDescriptions()->count()) {
            $description = (string)$update->getDescription($localizationHelper->getCurrentLocalization());
            if ($description) {
                $item->setExtra('description', $description);
            }
        }
    }

    /**
     * @param MenuUpdateInterface $update
     * @param ItemInterface $menu
     * @param array $options
     * @return ItemInterface|null
     */
    protected static function findOrCreateMenuItem(
        MenuUpdateInterface $update,
        ItemInterface $menu,
        array $options = []
    ) {
        $item = self::findMenuItem($menu, $update->getKey());
        if ($item === null && !$update->isCustom()) {
            return null;
        }

        if (null !== $update->getParentKey()) {
            $parentItem = self::findMenuItem($menu, $update->getParentKey());
        }
        $parentItem ??= $menu;

        if ($item === null) {
            $item = $parentItem->addChild($update->getKey(), $options);
        }

        if ($item->getParent()->getName() !== $parentItem->getName()) {
            $item->getParent()->removeChild($item->getName());
            $item = $parentItem->addChild($item, $options);
        }
        return $item;
    }

    /**
     * Find item by name in menu
     *
     * @param ItemInterface $menuItem
     * @param string $name
     *
     * @return ItemInterface|null
     */
    public static function findMenuItem(ItemInterface $menuItem, $name): ?ItemInterface
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
     * Check if menu has items that exceed max nesting level
     *
     * @param ItemInterface $menu
     * @param ItemInterface $item
     *
     * @return ItemInterface|null
     */
    public static function getItemExceededMaxNestingLevel(ItemInterface $menu, ItemInterface $item)
    {
        $maxNestingLevel = $menu->getExtra('max_nesting_level', 0);

        if ($maxNestingLevel && $item->getLevel() > $maxNestingLevel) {
            return $item;
        }

        foreach ($item->getChildren() as $child) {
            $result = self::getItemExceededMaxNestingLevel($menu, $child);
            if ($result) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Generates cache key for menu updates in specified scope
     *
     * @param string $menuName
     * @param Scope $scope
     * @return string
     */
    public static function generateKey($menuName, Scope $scope)
    {
        return $menuName.'_'.$scope->getId();
    }
}
