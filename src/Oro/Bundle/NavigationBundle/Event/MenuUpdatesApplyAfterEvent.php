<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Oro\Bundle\NavigationBundle\MenuUpdateApplier\Model\MenuUpdatesApplyResult;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after the menu updates are applied to menu.
 */
class MenuUpdatesApplyAfterEvent extends Event
{
    private MenuUpdatesApplyResult $menuUpdatesApplyResult;

    public function __construct(MenuUpdatesApplyResult $menuUpdatesApplyResult)
    {
        $this->menuUpdatesApplyResult = $menuUpdatesApplyResult;
    }

    public function getApplyResult(): MenuUpdatesApplyResult
    {
        return $this->menuUpdatesApplyResult;
    }
}
