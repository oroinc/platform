<?php
namespace Oro\Bundle\NavigationBundle\Menu;

interface MenuUpdateProviderInterface
{
    /**
     * @param $menu
     * @return mixed
     */
    public function getUpdates($menu);
}
