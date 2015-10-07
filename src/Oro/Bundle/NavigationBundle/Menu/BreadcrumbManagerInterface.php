<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\ItemInterface;

use Symfony\Component\Routing\Route;

interface BreadcrumbManagerInterface
{
    /**
     * @param Route|string $route
     * @return bool
     */
    public function supports($route = null);

    /**
     * @param string $menuName
     * @param bool $isInverse
     * @return array
     */
    public function getBreadcrumbs($menuName, $isInverse = true);

    /**
     * @param ItemInterface|string $menu
     * @param array $pathName
     * @param array $options
     * @return ItemInterface
     */
    public function getMenu($menu, array $pathName = [], array $options = []);

    /**
     * @param ItemInterface|string $menu
     * @return ItemInterface
     */
    public function getCurrentMenuItem($menu);

    /**
     * @param string $menuName
     * @param ItemInterface $item
     * @param bool $isInverse
     * @return array
     */
    public function getBreadcrumbArray($menuName, $item, $isInverse = true);

    /**
     * @param ItemInterface|string $menu
     * @param string $route
     * @return array
     */
    public function getBreadcrumbLabels($menu, $route);
}
