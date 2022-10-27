<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Symfony\Component\Routing\Route;

/**
 * Delegates a receiving breadcrumb information to child managers.
 */
class ChainBreadcrumbManager implements BreadcrumbManagerInterface
{
    /** @var iterable|BreadcrumbManagerInterface[] */
    private $managers;

    /**
     * @param iterable|BreadcrumbManagerInterface[] $managers
     */
    public function __construct(iterable $managers)
    {
        $this->managers = $managers;
    }

    /** {@inheritdoc} */
    public function getBreadcrumbs($menuName, $isInverse = true, $route = null)
    {
        return $this->getSupportedManager($route)->getBreadcrumbs($menuName, $isInverse, $route);
    }

    /** {@inheritdoc} */
    public function getMenu($menu, array $pathName = [], array $options = [])
    {
        return $this->getSupportedManager()->getMenu($menu, $pathName, $options);
    }

    /** {@inheritdoc} */
    public function getCurrentMenuItem($menu)
    {
        return $this->getSupportedManager()->getCurrentMenuItem($menu);
    }

    /** {@inheritdoc} */
    public function getBreadcrumbArray($menuName, $item, $isInverse = true)
    {
        return $this->getSupportedManager()->getBreadcrumbArray($menuName, $item, $isInverse);
    }

    /** {@inheritdoc} */
    public function getBreadcrumbLabels($menu, $route)
    {
        return $this->getSupportedManager($route)->getBreadcrumbLabels($menu, $route);
    }

    /** {@inheritdoc} */
    public function supports($route = null)
    {
        foreach ($this->managers as $manager) {
            if ($manager->supports($route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Route|string $route
     *
     * @return BreadcrumbManagerInterface
     */
    private function getSupportedManager($route = null): BreadcrumbManagerInterface
    {
        foreach ($this->managers as $manager) {
            if ($manager->supports($route)) {
                return $manager;
            }
        }

        throw new \LogicException('A breadcrumb manager was not found.');
    }
}
