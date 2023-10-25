<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is triggered before menu updates applying to menu
 */
class MenuUpdatesApplyBeforeEvent extends Event
{
    const NAME = 'oro_menu.menu_updates_apply_before';

    private array $menuUpdates = [];

    public function __construct(array $menuUpdates)
    {
        $this->menuUpdates = $menuUpdates;
    }

    public function getMenuUpdates(): array
    {
        return $this->menuUpdates;
    }
}
