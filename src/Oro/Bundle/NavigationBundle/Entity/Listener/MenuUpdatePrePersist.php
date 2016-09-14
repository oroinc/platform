<?php

namespace Oro\Bundle\NavigationBundle\Entity\Listener;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;

class MenuUpdatePrePersist
{
    /**
     * @param MenuUpdate $menuUpdate
     */
    public function prePersist(MenuUpdate $menuUpdate)
    {
        if ($menuUpdate->getKey() === null) {
            $menuUpdate->setKey(uniqid());
        }
    }
}
