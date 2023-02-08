<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Oro\Bundle\NavigationBundle\MenuUpdate\Applier\Model\MenuUpdateApplierContext;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after the menu updates are applied to menu.
 */
class MenuUpdatesApplyAfterEvent extends Event
{
    private MenuUpdateApplierContext $menuUpdateApplierContext;

    public function __construct(MenuUpdateApplierContext $menuUpdateApplierContext)
    {
        $this->menuUpdateApplierContext = $menuUpdateApplierContext;
    }

    public function getContext(): MenuUpdateApplierContext
    {
        return $this->menuUpdateApplierContext;
    }
}
