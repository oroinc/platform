<?php

namespace Oro\Bundle\NavigationBundle\Menu\Provider;

// TODO: remove this class in ticket BB-5468
interface OwnershipProviderInterface
{
    /**
     * @return string
     */
    public function getType();

    /**
     * @return integer
     */
    public function getId();

    /**
     * @param string $menuName
     * @return \Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface[]
     */
    public function getMenuUpdates($menuName);
}
