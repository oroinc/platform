<?php

namespace Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

/**
 * Propagates menu update with menu item data by delegating call to inner propagators.
 */
class CompositePropagator implements MenuItemToMenuUpdatePropagatorInterface
{
    /** @var iterable<MenuItemToMenuUpdatePropagatorInterface> */
    private iterable $propagators;

    public function __construct(iterable $propagators)
    {
        $this->propagators = $propagators;
    }

    public function isApplicable(MenuUpdateInterface $menuUpdate, ItemInterface $menuItem, string $strategy): bool
    {
        foreach ($this->propagators as $propagator) {
            if ($propagator->isApplicable($menuUpdate, $menuItem, $strategy)) {
                return true;
            }
        }

        return false;
    }

    public function propagateFromMenuItem(
        MenuUpdateInterface $menuUpdate,
        ItemInterface $menuItem,
        string $strategy
    ): void {
        foreach ($this->propagators as $propagator) {
            if ($propagator->isApplicable($menuUpdate, $menuItem, $strategy)) {
                $propagator->propagateFromMenuItem($menuUpdate, $menuItem, $strategy);
            }
        }
    }
}
