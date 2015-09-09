<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Routing\Route;

class ChainBreadcrumbManager implements BreadcrumbManagerInterface
{
    /**
     * @var ArrayCollection|BreadcrumbManager[]
     */
    protected $managers;

    /**
     * @var BreadcrumbManager
     */
    protected $defaultManager;

    public function __construct()
    {
        $this->managers = new ArrayCollection();
    }

    /**
     * @param BreadcrumbManagerInterface $manager
     */
    public function addManager(BreadcrumbManagerInterface $manager)
    {
        if ($this->managers->contains($manager)) {
            return;
        }

        $this->managers->add($manager);
    }

    /**
     * @param BreadcrumbManagerInterface $defaultManager
     */
    public function setDefaultManager(BreadcrumbManagerInterface $defaultManager)
    {
        $this->defaultManager = $defaultManager;
    }

    /**
     * @param Route|string $route
     * @return BreadcrumbManager
     */
    public function getSupportedProvider($route = null)
    {
        foreach ($this->managers as $manager) {
            if ($manager->supports($route)) {
                return $manager;
            }
        }

        return $this->defaultManager;
    }

    /** {@inheritdoc} */
    public function getBreadcrumbs($menuName, $isInverse = true)
    {
        return $this->getSupportedProvider()->getBreadcrumbs($menuName, $isInverse);
    }

    /** {@inheritdoc} */
    public function getMenu($menu, array $pathName = [], array $options = [])
    {
        return $this->getSupportedProvider()->getMenu($menu, $pathName, $options);
    }

    /** {@inheritdoc} */
    public function getCurrentMenuItem($menu)
    {
        return $this->getSupportedProvider()->getCurrentMenuItem($menu);
    }

    /** {@inheritdoc} */
    public function getBreadcrumbArray($menuName, $item, $isInverse = true)
    {
        return $this->getSupportedProvider()->getBreadcrumbArray($menuName, $item, $isInverse);
    }

    /** {@inheritdoc} */
    public function getBreadcrumbLabels($menu, $route)
    {
        return $this->getSupportedProvider($route)->getBreadcrumbLabels($menu, $route);
    }

    /** {@inheritdoc} */
    public function supports($route = null)
    {
        return (bool)$this->getSupportedProvider($route);
    }
}
