<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is triggered before create/update of MenuUpdate in specified scope.
 */
class BeforeMenuHandleUpdateEvent extends Event
{
    public const NAME = 'oro_menu.before_menu_handle_update';

    public function __construct(private string $menuName, private array $context)
    {
    }

    public function getMenuName(): string
    {
        return $this->menuName;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
