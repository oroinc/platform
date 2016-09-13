<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Oro\Bundle\NavigationBundle\Entity\AbstractMenuUpdate;

interface MenuUpdateProviderInterface
{
    /**
     * Retrieve list of menu updates
     *
     * @param string $menu
     *
     * @return AbstractMenuUpdate[]
     */
    public function getUpdates($menu);
}
