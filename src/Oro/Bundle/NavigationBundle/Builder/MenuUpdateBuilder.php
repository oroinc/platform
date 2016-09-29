<?php

namespace Oro\Bundle\NavigationBundle\Builder;

use Knp\Menu\ItemInterface;

use Oro\Bundle\NavigationBundle\Exception\ProviderNotFoundException;
use Oro\Bundle\NavigationBundle\Helper\MenuUpdateHelper;
use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;
use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProviderInterface;

class MenuUpdateBuilder implements BuilderInterface
{
    const OWNERSHIP_TYPE_OPTION = 'ownershipType';

    /** @var MenuUpdateProviderInterface[] */
    private $providers = [];
    
    /** @var MenuUpdateHelper */
    protected $menuUpdateHelper;
    
    /**
     * @param MenuUpdateHelper $menuUpdateHelper
     */
    public function __construct(MenuUpdateHelper $menuUpdateHelper)
    {
        $this->menuUpdateHelper = $menuUpdateHelper;
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
                $this->menuUpdateHelper->updateMenuItem($update, $menu);
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
