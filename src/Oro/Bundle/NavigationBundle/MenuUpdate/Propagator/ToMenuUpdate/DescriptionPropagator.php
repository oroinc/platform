<?php

namespace Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Menu\Helper\MenuUpdateHelper;

/**
 * Propagates menu item extra description option to menu update.
 */
class DescriptionPropagator implements MenuItemToMenuUpdatePropagatorInterface
{
    private MenuUpdateHelper $menuUpdateHelper;

    public function __construct(MenuUpdateHelper $menuUpdateHelper)
    {
        $this->menuUpdateHelper = $menuUpdateHelper;
    }

    public function isApplicable(MenuUpdateInterface $menuUpdate, ItemInterface $menuItem, string $strategy): bool
    {
        return $strategy === self::STRATEGY_FULL;
    }

    public function propagateFromMenuItem(
        MenuUpdateInterface $menuUpdate,
        ItemInterface $menuItem,
        string $strategy
    ): void {
        $descriptions = $menuUpdate->getDescriptions();
        if ($descriptions?->count()) {
            return;
        }

        $this->menuUpdateHelper
            ->applyLocalizedFallbackValue(
                $menuUpdate,
                $menuItem->getExtra(MenuUpdateInterface::DESCRIPTION),
                'description',
                'text'
            );
    }
}
