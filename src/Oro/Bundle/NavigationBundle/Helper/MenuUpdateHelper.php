<?php

namespace Oro\Bundle\NavigationBundle\Helper;

use Knp\Menu\ItemInterface;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

use Oro\Component\PropertyAccess\PropertyAccessor;

class MenuUpdateHelper
{
    /** @var LocalizationHelper */
    private $localizationHelper;

    /**
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(LocalizationHelper $localizationHelper)
    {
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param MenuUpdateInterface $update
     * @param ItemInterface $item
     * @param string $menu
     * @param array $extrasMapping
     */
    public function updateMenuUpdate(
        MenuUpdateInterface $update,
        ItemInterface $item,
        $menu,
        array $extrasMapping = ['position' => 'priority']
    ) {
        $propertyAccessor = new PropertyAccessor();
        $propertyAccessor->setValue($update, 'key', $item->getName());
        $propertyAccessor->setValue($update, 'uri', $item->getUri());

        if ($update->getId() === null || $update->getTitles()->count() <= 0) {
            $propertyAccessor->setValue($update, 'defaultTitle', $item->getLabel());
        }

        if ($item->getParent()) {
            $propertyAccessor->setValue($update, 'parentKey', $item->getParent()->getName());
        }

        $update->setActive($item->isDisplayed());
        $update->setMenu($menu);
        $update->setExistsInNavigationYml(!$item->getExtra('userDefined', false));

        foreach ($item->getExtras() as $key => $value) {
            if (array_key_exists($key, $extrasMapping)) {
                $key = $extrasMapping[$key];
            }
            if ($propertyAccessor->isWritable($update, $key)) {
                $propertyAccessor->setValue($update, $key, $value);
            }
        }
    }

    /**
     * @param MenuUpdateInterface $update
     * @param ItemInterface $menu
     *
     * @return ItemInterface
     */
    public function updateMenuItem(MenuUpdateInterface $update, ItemInterface $menu)
    {
        $item = $this->findMenuItem($menu, $update->getKey());
        $parentItem = $this->findMenuItem($menu, $update->getParentKey());
        $parentItem = $parentItem === null ? $menu : $parentItem;

        if (!$item instanceof ItemInterface) {
            $item = $parentItem->addChild($update->getKey());
            $item->setExtra('userDefined', true);
        }

        if ($item->getParent()->getName() != $parentItem->getName()) {
            $item->getParent()->removeChild($item->getName());
            $item = $parentItem->addChild($item);
        }

        if ($update->getTitles()->count()) {
            $title = $this->localizationHelper->getLocalizedValue($update->getTitles());
            $item->setLabel($title->getString());
        }

        if ($update->getUri()) {
            $item->setUri($update->getUri());
        }

        $item->setDisplay($update->isActive());

        foreach ($update->getExtras() as $key => $extra) {
            $item->setExtra($key, $extra);
        }

        $item->setExtra('editable', true);

        return $item;
    }

    /**
     * @param ItemInterface $menuItem
     * @param string $name
     *
     * @return ItemInterface|null
     */
    public function findMenuItem(ItemInterface $menuItem, $name)
    {
        $item = $menuItem->getChild($name);
        if (!$item) {
            foreach ($menuItem->getChildren() as $child) {
                $item = $this->findMenuItem($child, $name);
                if ($item instanceof ItemInterface) {
                    break;
                }
            }
        }

        return $item;
    }
}
