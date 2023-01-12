<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Event\MenuUpdatesApplyAfterEvent;
use Oro\Bundle\NavigationBundle\MenuUpdate\Applier\Model\MenuUpdateApplierContext;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;

/**
 * Moves orphaned menu items into their parents, if any.
 */
class OrphanItemsBuilder implements BuilderInterface
{
    /**
     * @var array<string,MenuUpdateApplierContext> Contexts indexed by menu name.
     */
    private array $menuUpdateApplierContexts = [];

    public function build(ItemInterface $menu, array $options = [], $alias = null): void
    {
        if (!$menu->isDisplayed()) {
            return;
        }

        $menuUpdateApplierContext = $this->menuUpdateApplierContexts[$menu->getName()] ?? null;
        if ($menuUpdateApplierContext === null) {
            return;
        }

        foreach ($menuUpdateApplierContext->getOrphanedItems() as $parentMenuItemName => $menuItemsByName) {
            $parentMenuItem = MenuUpdateUtils::findMenuItem($menu, $parentMenuItemName);
            if (!$parentMenuItem) {
                continue;
            }

            foreach ($menuItemsByName as $menuItemName => $menuItem) {
                if ($menuUpdateApplierContext->isLostItem($menuItem->getName())) {
                    continue;
                }

                $menuItem->getParent()?->removeChild($menuItemName);
                $parentMenuItem->addChild($menuItem);
                $menuUpdateApplierContext->removeOrphanedItem($parentMenuItemName, $menuItemName);
            }
        }

        unset($this->menuUpdateApplierContexts[$menu->getName()]);
    }

    public function onMenuUpdatesApplyAfter(MenuUpdatesApplyAfterEvent $event): void
    {
        $menuUpdateApplierContext = $event->getContext();
        $this->menuUpdateApplierContexts[$menuUpdateApplierContext->getMenu()->getName()] = $menuUpdateApplierContext;
    }
}
