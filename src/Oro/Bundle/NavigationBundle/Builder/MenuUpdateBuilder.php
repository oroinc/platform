<?php

namespace Oro\Bundle\NavigationBundle\Builder;

use Knp\Menu\ItemInterface;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Exception\MaxNestingLevelExceededException;
use Oro\Bundle\NavigationBundle\Exception\ProviderNotFoundException;
use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;
use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProviderInterface;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;

class MenuUpdateBuilder implements BuilderInterface
{
    const OWNERSHIP_TYPE_OPTION = 'ownershipType';

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
        $ownershipType = array_key_exists(self::OWNERSHIP_TYPE_OPTION, $options) ?
            $options[self::OWNERSHIP_TYPE_OPTION] : false;
        $area = $menu->getExtra('area', ConfigurationBuilder::DEFAULT_AREA);
        $provider = $this->getProvider($area);
        $menuName = $menu->getName();
        foreach ($provider->getUpdates($menuName, $ownershipType) as $update) {
            if ($update->getMenu() == $menuName) {
                MenuUpdateUtils::updateMenuItem($update, $menu, $this->localizationHelper);
            }
        }

        /** @var ItemInterface $item */
        foreach ($menu->getChildren() as $item) {
            $item = MenuUpdateUtils::getItemExceededMaxNestingLevel($menu, $item);
            if ($item) {
                throw new MaxNestingLevelExceededException(
                    sprintf(
                        "Item \"%s\" exceeded max nesting level in menu \"%s\".",
                        $item->getLabel(),
                        $menu->getLabel()
                    )
                );
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
}
