<?php

namespace Oro\Bundle\NavigationBundle\Model;

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
     * @param $menuName
     * @return \Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface[]
     */
    public function getMenuUpdates($menuName);
}
