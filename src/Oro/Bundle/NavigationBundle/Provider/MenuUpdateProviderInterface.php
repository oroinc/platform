<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

interface MenuUpdateProviderInterface
{
    /**
     * @param ItemInterface $menuItem
     * @param array         $options
     *
     * @return MenuUpdateInterface[]
     */
    public function getMenuUpdatesForMenuItem(ItemInterface $menuItem, array $options = []);
}
