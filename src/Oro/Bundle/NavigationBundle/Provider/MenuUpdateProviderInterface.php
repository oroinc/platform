<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;

interface MenuUpdateProviderInterface
{
    /**
     * @param ItemInterface $menuItem
     * @param array         $options
     *
     * @return MenuUpdate[]
     */
    public function getMenuUpdatesForMenuItem(ItemInterface $menuItem, array $options = []);
}
