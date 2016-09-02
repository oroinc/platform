<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\NavigationBundle\Model\MenuUpdate as MenuUpdateModel;

/**
 * @ORM\Entity()
 * @ORM\Table(name="oro_navigation_menu_update")
 */
class MenuUpdate extends MenuUpdateModel
{
    const OWNERSHIP_BUSINESS_UNIT = 3;
    const OWNERSHIP_USER          = 4;

    /**
     * {@inheritdoc}
     */
    public function getExtras()
    {
        return [];
    }
}
