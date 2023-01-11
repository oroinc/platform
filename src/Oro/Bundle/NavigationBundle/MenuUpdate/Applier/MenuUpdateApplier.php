<?php

namespace Oro\Bundle\NavigationBundle\MenuUpdate\Applier;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Applier\Model\MenuUpdateApplierContext;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuItem\MenuUpdateToMenuItemPropagatorInterface;

/**
 * Applies a menu update to menu.
 */
class MenuUpdateApplier implements MenuUpdateApplierInterface
{
    public const PROPAGATION_STRATEGY = 'menuUpdatePropagationStrategy';

    private MenuUpdateToMenuItemPropagatorInterface $menuUpdateToMenuItemPropagator;

    public function __construct(MenuUpdateToMenuItemPropagatorInterface $menuUpdateToMenuItemPropagator)
    {
        $this->menuUpdateToMenuItemPropagator = $menuUpdateToMenuItemPropagator;
    }

    public function applyMenuUpdate(
        MenuUpdateInterface $menuUpdate,
        ItemInterface $menu,
        array $menuOptions,
        ?MenuUpdateApplierContext $context
    ): int {
        if ($context === null) {
            $context = new MenuUpdateApplierContext($menu);
        }

        $resultCode = 0;
        $targetMenuItemName = $menuUpdate->getKey();
        $parentMenuItemName = $menuUpdate->getParentKey() ?? $context->getMenu()->getName();

        $parentFound = true;
        $parentMenuItem = $context->getMenuItemByName($parentMenuItemName);
        if ($parentMenuItem === null) {
            $parentFound = false;
            $parentMenuItem = $menu;
        }

        $targetMenuItem = $context->getMenuItemByName($targetMenuItemName);
        if ($targetMenuItem === null) {
            $targetMenuItem = $this->createMenuItem($menuUpdate, $parentMenuItem, $menuOptions, $context, $resultCode);

            // Moves orphans into the newly created menu item.
            $orphanItems = $context->getOrphanedItems($targetMenuItemName);
            if (count($orphanItems) > 0) {
                foreach ($orphanItems as $orphanItem) {
                    $this->move($orphanItem, $targetMenuItem);
                }

                $context->removeOrphanedItems($targetMenuItemName);
            }
        } else {
            $resultCode |= self::RESULT_ITEM_UPDATED;
            $context->addUpdatedItem($targetMenuItem, $menuUpdate);

            if ($parentFound && $targetMenuItem->getParent()?->getName() !== $parentMenuItemName) {
                // Moves the menu item according to its menu update parent key.
                $this->move($targetMenuItem, $parentMenuItem);
                $context->removeOrphanedItem($targetMenuItemName, $targetMenuItemName);
            }
        }

        if (!$parentFound) {
            // Marks the menu item as orphan as it is not located inside the parent with menu update parent key.
            $context->addOrphanedItem($parentMenuItemName, $targetMenuItem, $menuUpdate);
            $resultCode |= self::RESULT_ITEM_ORPHANED;
        }

        $this->menuUpdateToMenuItemPropagator->propagateFromMenuUpdate(
            $targetMenuItem,
            $menuUpdate,
            $menuOptions[self::PROPAGATION_STRATEGY] ?? MenuUpdateToMenuItemPropagatorInterface::STRATEGY_FULL
        );

        return $resultCode;
    }

    private function createMenuItem(
        MenuUpdateInterface $menuUpdate,
        ItemInterface $parentMenuItem,
        array $menuOptions,
        MenuUpdateApplierContext $context,
        int &$resultCode
    ): ItemInterface {
        $menuItem = $parentMenuItem->addChild($menuUpdate->getKey(), $menuOptions);
        $context->addCreatedItem($menuItem, $menuUpdate);
        $resultCode |= self::RESULT_ITEM_CREATED;

        if (!$menuUpdate->isCustom() && !$menuUpdate->isSynthetic()) {
            $context->addLostItem($menuItem, $menuUpdate);
            $resultCode |= self::RESULT_ITEM_LOST;
        }

        return $menuItem;
    }

    private function move(ItemInterface $menuItem, ItemInterface $parentMenuItem): void
    {
        $menuItem->getParent()?->removeChild($menuItem->getName());
        $parentMenuItem->addChild($menuItem);
    }
}
