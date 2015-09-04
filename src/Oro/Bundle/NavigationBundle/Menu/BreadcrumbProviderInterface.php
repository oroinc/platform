<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Symfony\Component\Routing\Route;

interface BreadcrumbProviderInterface
{
    /**
     * @param Route|string $route
     * @return bool
     */
    public function supports($route = null);

    /**
     * @param $menuName
     * @param bool|true $isInverse
     * @return array
     */
    public function getBreadcrumbs($menuName, $isInverse = true);

    /**
     * @param \Knp\Menu\ItemInterface|string $menu
     * @param array $pathName
     * @param array $options
     * @return \Knp\Menu\ItemInterface
     */
    public function getMenu($menu, array $pathName = [], array $options = []);

    /**
     * @param $menu
     * @return \Knp\Menu\ItemInterface|null
     */
    public function getCurrentMenuItem($menu);

    /**
     * @param $menuName
     * @param $item
     * @param bool|true $isInverse
     * @return array
     */
    public function getBreadcrumbArray($menuName, $item, $isInverse = true);

    /**
     * @param \Knp\Menu\ItemInterface $menu
     * @param string $route
     * @return array
     */
    public function getBreadcrumbLabels($menu, $route);
}
