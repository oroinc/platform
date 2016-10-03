<?php

namespace Oro\Bundle\NavigationBundle\Utils;

use Knp\Menu\ItemInterface;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class MenuUpdateUtils
{
    /**
     * Apply changes from menu item to menu update
     *
     * @param MenuUpdateInterface $update
     * @param ItemInterface $item
     * @param string $menuName
     * @param array $extrasMapping
     */
    public static function updateMenuUpdate(
        MenuUpdateInterface $update,
        ItemInterface $item,
        $menuName,
        array $extrasMapping = ['position' => 'priority']
    ) {
        $accessor = PropertyAccess::createPropertyAccessor();

        self::setValue($accessor, $update, 'key', $item->getName());
        self::setValue($accessor, $update, 'uri', $item->getUri());
        self::setValue($accessor, $update, 'defaultTitle', $item->getLabel());

        $parentKey = $item->getParent() ? $item->getParent()->getName() : null;
        self::setValue($accessor, $update, 'parentKey', $parentKey);

        $update->setActive($item->isDisplayed());
        $update->setMenu($menuName);

        foreach ($item->getExtras() as $key => $value) {
            if (array_key_exists($key, $extrasMapping)) {
                $key = $extrasMapping[$key];
            }

            self::setValue($accessor, $update, $key, $value);
        }
    }

    /**
     * Apply changes from menu update to menu item
     *
     * @param MenuUpdateInterface $update
     * @param ItemInterface $menu
     * @param LocalizationHelper $localizationHelper
     *
     * @return ItemInterface
     */
    public static function updateMenuItem(
        MenuUpdateInterface $update,
        ItemInterface $menu,
        LocalizationHelper $localizationHelper
    ) {
        $item = self::findMenuItem($menu, $update->getKey());
        $parentItem = self::findMenuItem($menu, $update->getParentKey());
        $parentItem = $parentItem === null ? $menu : $parentItem;

        if (!$item instanceof ItemInterface) {
            $item = $parentItem->addChild($update->getKey());
        } else {
            $update->setExistsInNavigationYml(true);
        }

        if ($item->getParent()->getName() != $parentItem->getName()) {
            $item->getParent()->removeChild($item->getName());
            $item = $parentItem->addChild($item);
        }

        if ($update->getTitles()->count()) {
            $item->setLabel($update->getTitle($localizationHelper->getCurrentLocalization()));
        }

        if ($update->getUri()) {
            $item->setUri($update->getUri());
        }

        $item->setDisplay($update->isActive());

        foreach ($update->getExtras() as $key => $extra) {
            $item->setExtra($key, $extra);
        }
    }

    /**
     * Find item by name in menu
     *
     * @param ItemInterface $menuItem
     * @param string $name
     *
     * @return ItemInterface|null
     */
    public static function findMenuItem(ItemInterface $menuItem, $name)
    {
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
     * @param PropertyAccessor $accessor
     * @param MenuUpdateInterface $update
     * @param string $key
     * @param mixed $value
     */
    private static function setValue(PropertyAccessor $accessor, MenuUpdateInterface $update, $key, $value)
    {
        if ($accessor->isWritable($update, $key) && $accessor->getValue($update, $key) === null) {
            $accessor->setValue($update, $key, $value);
        }
    }
}
