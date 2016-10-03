<?php

namespace Oro\Bundle\NavigationBundle\Helper;

use Knp\Menu\ItemInterface;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

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
        $this->setMenuUpdateProperty($update, 'key', $item->getName());
        $this->setMenuUpdateProperty($update, 'uri', $item->getUri());

        if ($update->getId() === null || $update->getTitles()->count() <= 0) {
            $this->setMenuUpdateProperty($update, 'defaultTitle', $item->getLabel());
        }

        if ($item->getParent()) {
            $this->setMenuUpdateProperty($update, 'parentKey', $item->getParent()->getName());
        }

        $update->setActive($item->isDisplayed());
        $update->setMenu($menu);
        $update->setExistsInNavigationYml(!$item->getExtra('userDefined', false));

        foreach ($item->getExtras() as $key => $value) {
            if (array_key_exists($key, $extrasMapping)) {
                $key = $extrasMapping[$key];
            }

            $this->setMenuUpdateProperty($update, $key, $value);
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

    /**
     * @param MenuUpdateInterface $update
     * @param string $key
     * @param mixed $value
     */
    private function setMenuUpdateProperty(MenuUpdateInterface $update, $key, $value)
    {
        $method = 'get' . ucfirst($key);
        if (method_exists($update, $method)) {
            $method = 'set' . ucfirst($key);
            if (method_exists($update, $method)) {
                $update->{$method}($value);
            }
        }
    }
}
