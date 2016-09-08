<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Oro\Bundle\NavigationBundle\Entity\AbstractMenuUpdate;

interface MenuUpdateProviderInterface
{
    /**
     * Retrieve list of menu updates
     *
     * @return AbstractMenuUpdate[]
     */
    public function getUpdates();
}
