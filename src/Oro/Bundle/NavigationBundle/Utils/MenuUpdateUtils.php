<?php

namespace Oro\Bundle\NavigationBundle\Utils;

use Knp\Menu\ItemInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Menu\Helper\MenuUpdateHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class MenuUpdateUtils
{
    /**
     * @var PropertyAccessor
     */
    private static $propertyAccessor;

    /**
     * Apply changes from menu item to menu update
     *
     * @param MenuUpdateInterface $update
     * @param ItemInterface       $item
     * @param string              $menuName
     * @param MenuUpdateHelper    $menuUpdateHelper
     * @param array               $extrasMapping
     */
    public static function updateMenuUpdate(
        MenuUpdateInterface $update,
        ItemInterface $item,
        $menuName,
        MenuUpdateHelper $menuUpdateHelper,
        array $extrasMapping = ['position' => 'priority']
    ) {
        $accessor = self::getPropertyAccessor();

        self::setValue($accessor, $update, 'key', $item->getName());
        self::setValue($accessor, $update, 'uri', $item->getUri());

        $menuUpdateHelper->applyLocalizedFallbackValue($update, $item->getLabel(), 'title', 'string');

        if ($update->getTitles()->count() <= 0) {
            self::setValue($accessor, $update, 'defaultTitle', $item->getLabel());
        }

        $parent = $item->getParent();
        if ($parent) {
            $parentKey = $parent->getName() !== $menuName ? $parent->getName() : null;
            self::setValue($accessor, $update, 'parentKey', $parentKey);
        }

        $update->setActive($item->isDisplayed());
        $update->setMenu($menuName);

        foreach ($item->getExtras() as $key => $value) {
            if ($key === 'description') {
                $menuUpdateHelper->applyLocalizedFallbackValue($update, $item->getExtra($key), $key, 'text');
                continue;
            }

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
     * @param array $options
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

        $parentItem = self::findMenuItem($menu, $update->getParentKey());
        $parentItem = $parentItem === null ? $menu : $parentItem;

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

    /**
     * @param PropertyAccessor $accessor
     * @param MenuUpdateInterface $update
     * @param string $key
     * @param mixed $value
     */
    private static function setValue(PropertyAccessor $accessor, MenuUpdateInterface $update, $key, $value)
    {
        if ($accessor->isWritable($update, $key)) {
            $currentValue = $accessor->getValue($update, $key);
            if ($currentValue === null || is_bool($currentValue)) {
                $accessor->setValue($update, $key, $value);
            }
        }
    }

    /**
     * @return PropertyAccessor
     */
    private static function getPropertyAccessor()
    {
        if (!self::$propertyAccessor) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return self::$propertyAccessor;
    }
}
