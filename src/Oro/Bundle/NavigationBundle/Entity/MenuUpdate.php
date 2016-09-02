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
    /**
     * {@inheritdoc}
     */
    public function getExtras()
    {
        return [];
    }
}
