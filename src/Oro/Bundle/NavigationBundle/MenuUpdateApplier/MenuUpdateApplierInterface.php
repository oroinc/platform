<?php

namespace Oro\Bundle\NavigationBundle\MenuUpdateApplier;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\MenuUpdateApplier\Model\MenuUpdatesApplyResult;

/**
 * Interface for menu update appliers.
 */
interface MenuUpdateApplierInterface
{
    /**
     * @param ItemInterface $menuItem
     * @param MenuUpdateInterface[] $menuUpdates
     * @param array $menuOptions Menu options to use when creating a new menu item. See {@see ItemInterface::addChild}.
     *
     * @return MenuUpdatesApplyResult
     */
    public function applyMenuUpdates(
        ItemInterface $menuItem,
        array $menuUpdates,
        array $menuOptions = []
    ): MenuUpdatesApplyResult;
}
