<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Routing\Route;

class ChainBreadcrumbManager
{
    /**
     * @var ArrayCollection|BreadcrumbManager[]
     */
    protected $providers;

    /**
     * @var BreadcrumbManager
     */
    protected $defaultProvider;

    /**
     * @param BreadcrumbManager $defaultProvider
     */
    public function __construct(BreadcrumbManager $defaultProvider)
    {
        $this->providers = new ArrayCollection();
        $this->defaultProvider = $defaultProvider;
    }

    /**
     * @param BreadcrumbProviderInterface $provider
     */
    public function addProvider(BreadcrumbProviderInterface $provider)
    {
        if ($this->providers->contains($provider)) {
            return;
        }

        $this->providers->add($provider);
    }

    /**
     * @param BreadcrumbManager $defaultProvider
     */
    public function setDefaultProvider($defaultProvider)
    {
        $this->defaultProvider = $defaultProvider;
    }

    /**
     * @param Route|string $route
     * @return BreadcrumbManager
     */
    public function getSupportedProvider($route = null)
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($route)) {
                return $provider;
            }
        }

        return $this->defaultProvider;
    }

    /**
     * @param $menuName
     * @param bool|true $isInverse
     * @return array
     */
    public function getBreadcrumbs($menuName, $isInverse = true)
    {
        return $this->getSupportedProvider()->getBreadcrumbs($menuName, $isInverse);
    }

    /**
     * @param \Knp\Menu\ItemInterface|string $menu
     * @param array $pathName
     * @param array $options
     * @return \Knp\Menu\ItemInterface
     */
    public function getMenu($menu, array $pathName = [], array $options = [])
    {
        return $this->getSupportedProvider()->getMenu($menu, $pathName, $options);
    }

    /**
     * @param $menu
     * @return \Knp\Menu\ItemInterface|null
     */
    public function getCurrentMenuItem($menu)
    {
        return $this->getSupportedProvider()->getCurrentMenuItem($menu);
    }

    /**
     * @param $menuName
     * @param $item
     * @param bool|true $isInverse
     * @return array
     */
    public function getBreadcrumbArray($menuName, $item, $isInverse = true)
    {
        return $this->getSupportedProvider()->getBreadcrumbArray($menuName, $item, $isInverse);
    }

    /**
     * @param \Knp\Menu\ItemInterface|string $menu
     * @param string $route
     * @return array
     */
    public function getBreadcrumbLabels($menu, $route)
    {
        return $this->getSupportedProvider($route)->getBreadcrumbLabels($menu, $route);
    }
}
