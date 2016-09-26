<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

interface MenuUpdateProviderInterface
{
    /**
     * Retrieve list of menu updates
     *
     * @param int $ownershipType
     * @param string $menu
     *
     * @return MenuUpdateInterface[]
     */
    public function getUpdates($menu, $ownershipType);
}
