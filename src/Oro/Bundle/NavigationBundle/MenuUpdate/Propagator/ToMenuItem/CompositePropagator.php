<?php

namespace Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuItem;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

/**
 * Propagates menu item with menu update data by delegating call to inner propagators.
 */
class CompositePropagator implements MenuUpdateToMenuItemPropagatorInterface
{
    /** @var iterable<MenuUpdateToMenuItemPropagatorInterface> */
    private iterable $propagators;

    public function __construct(iterable $propagators)
    {
        $this->propagators = $propagators;
    }

    public function isApplicable(ItemInterface $menuItem, MenuUpdateInterface $menuUpdate, string $strategy): bool
    {
        foreach ($this->propagators as $propagator) {
            if ($propagator->isApplicable($menuItem, $menuUpdate, $strategy)) {
                return true;
            }
        }

        return false;
    }

    public function propagateFromMenuUpdate(
        ItemInterface $menuItem,
        MenuUpdateInterface $menuUpdate,
        string $strategy
    ): void {
        foreach ($this->propagators as $propagator) {
            if ($propagator->isApplicable($menuItem, $menuUpdate, $strategy)) {
                $propagator->propagateFromMenuUpdate($menuItem, $menuUpdate, $strategy);
            }
        }
    }
}
