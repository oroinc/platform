<?php

namespace Oro\Bundle\NavigationBundle\MenuUpdate\Applier;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Applier\Model\MenuUpdateApplierContext;

/**
 * Interface for menu update appliers.
 */
interface MenuUpdateApplierInterface
{
    public const RESULT_ITEM_CREATED = 1;
    public const RESULT_ITEM_UPDATED = 2;
    public const RESULT_ITEM_ORPHANED = 4;
    public const RESULT_ITEM_LOST = 8;

    /**
     * @param MenuUpdateInterface $menuUpdate
     * @param ItemInterface $menu
     * @param array $menuOptions Menu options to use when creating a new menu item. See {@see ItemInterface::addChild}.
     * @param MenuUpdateApplierContext|null $context
     *
     * @return int Result code. One or combination of MenuUpdateApplierInterface::RESULT_ITEM_* constants.
     */
    public function applyMenuUpdate(
        MenuUpdateInterface $menuUpdate,
        ItemInterface $menu,
        array $menuOptions,
        ?MenuUpdateApplierContext $context
    ): int;
}
