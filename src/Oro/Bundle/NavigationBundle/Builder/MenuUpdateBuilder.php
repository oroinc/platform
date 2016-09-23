<?php

namespace Oro\Bundle\NavigationBundle\Builder;

use Knp\Menu\ItemInterface;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Exception\ProviderNotFoundException;
use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;
use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProviderInterface;

class MenuUpdateBuilder implements BuilderInterface
{
    /** @var MenuUpdateProviderInterface[] */
    private $providers = [];
    
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
     * {@inheritdoc}
     */
    public function build(ItemInterface $menu, array $options = [], $alias = null)
    {
        $area = $menu->getExtra('area', ConfigurationBuilder::DEFAULT_AREA);
        $provider = $this->getProvider($area);
        $menuName = $menu->getName();
        foreach ($provider->getUpdates($menuName) as $update) {
            if ($update->getMenu() == $menuName) {
                $this->applyUpdate($menu, $update);
            }
        }
    }

    /**
     * @param string $area
     * @param MenuUpdateProviderInterface $provider
     *
     * @return MenuUpdateBuilder
     */
    public function addProvider($area, MenuUpdateProviderInterface $provider)
    {
        $this->providers[$area] = $provider;

        return $this;
    }

    /**
     * @param $area
     *
     * @return MenuUpdateProviderInterface
     */
    private function getProvider($area)
    {
        if (!array_key_exists($area, $this->providers)) {
            throw new ProviderNotFoundException(sprintf("Provider related to \"%s\" area not found.", $area));
        }
        
        return $this->providers[$area];
    }

    /**
     * @param ItemInterface $menu
     * @param MenuUpdateInterface $update
     */
    private function applyUpdate(ItemInterface $menu, MenuUpdateInterface $update)
    {
        $item = $this->findMenuItem($menu, $update->getKey());
        $parentItem = $this->findMenuItem($menu, $update->getParentKey());
        $parentItem = $parentItem === null ? $menu : $parentItem;

        if (!$item instanceof ItemInterface) {
            $item = $parentItem->addChild($update->getKey());
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
    }

    /**
     * @param ItemInterface $menuItem
     * @param string $key
     *
     * @return ItemInterface|null
     */
    private function findMenuItem(ItemInterface $menuItem, $key)
    {
        $item = $menuItem->getChild($key);
        if (!$item) {
            foreach ($menuItem->getChildren() as $child) {
                $item = $this->findMenuItem($child, $key);
                if ($item instanceof ItemInterface) {
                    break;
                }
            }
        }

        return $item;
    }
}
