<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Event\MenuUpdatesApplyAfterEvent;
use Oro\Bundle\NavigationBundle\MenuUpdate\Applier\MenuUpdateApplierInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Applier\Model\MenuUpdateApplierContext;
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
        if (!$menu->isDisplayed()) {
            return;
        }

        $menuUpdates = $this->menuUpdateProvider->getMenuUpdatesForMenuItem($menu, $options);
        if (!$menuUpdates) {
            return;
        }

        $context = new MenuUpdateApplierContext($menu);
        foreach ($menuUpdates as $menuUpdate) {
            $this->menuUpdateApplier->applyMenuUpdate($menuUpdate, $menu, $options, $context);
        }

        $event = new MenuUpdatesApplyAfterEvent($context);
        $this->eventDispatcher->dispatch($event);
    }
}
