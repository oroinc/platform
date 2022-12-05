<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Event\MenuUpdatesApplyAfterEvent;
use Oro\Bundle\NavigationBundle\MenuUpdateApplier\MenuUpdateApplierInterface;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Applies menu updates to the menu items.
 */
class MenuUpdateBuilder implements BuilderInterface
{
    private MenuUpdateProviderInterface $menuUpdateProvider;

    private MenuUpdateApplierInterface $menuUpdateApplier;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        MenuUpdateProviderInterface $menuUpdateProvider,
        MenuUpdateApplierInterface $menuUpdateApplier,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->menuUpdateProvider = $menuUpdateProvider;
        $this->menuUpdateApplier = $menuUpdateApplier;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function build(ItemInterface $menu, array $options = [], $alias = null): void
    {
        $menuUpdates = $this->menuUpdateProvider->getMenuUpdatesForMenuItem($menu, $options);
        if (!$menuUpdates) {
            return;
        }

        $menuUpdatesApplyResult = $this->menuUpdateApplier->applyMenuUpdates($menu, $menuUpdates, $options);

        $this->eventDispatcher->dispatch(new MenuUpdatesApplyAfterEvent($menuUpdatesApplyResult));
    }
}
