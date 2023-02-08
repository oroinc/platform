<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Event\MenuUpdatesApplyAfterEvent;
use Oro\Bundle\NavigationBundle\MenuUpdate\Applier\Model\MenuUpdateApplierContext;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;

/**
 * Removes lost menu items from menu. Moves custom and synthetic items from the removed lost items' children.
 */
class LostItemsBuilder implements BuilderInterface
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

        foreach ($menuUpdateApplierContext->getLostItems() as $menuItem) {
            $menuItem->getParent()?->removeChild($menuItem);

            foreach (MenuUpdateUtils::createRecursiveIterator($menuItem) as $removedItemChild) {
                if ($removedItemChild->getExtra(MenuUpdateInterface::IS_CUSTOM)
                    || $removedItemChild->getExtra(MenuUpdateInterface::IS_SYNTHETIC)) {
                    $removedItemChild->getParent()?->removeChild($removedItemChild->getName());
                    $menu->addChild($removedItemChild);
                }
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
