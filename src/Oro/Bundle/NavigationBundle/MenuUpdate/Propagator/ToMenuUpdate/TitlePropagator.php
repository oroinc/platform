<?php

namespace Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Menu\Helper\MenuUpdateHelper;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Propagates menu item title to menu update.
 */
class TitlePropagator implements MenuItemToMenuUpdatePropagatorInterface
{
    private PropertyAccessorInterface $propertyAccessor;

    private MenuUpdateHelper $menuUpdateHelper;

    public function __construct(PropertyAccessorInterface $propertyAccessor, MenuUpdateHelper $menuUpdateHelper)
    {
        $this->propertyAccessor = $propertyAccessor;
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
        $titles = $menuUpdate->getTitles();
        if ($titles?->count()) {
            return;
        }

        $titlesToPropagate = $menuItem->getExtra(MenuUpdateInterface::TITLES);
        if (is_iterable($titlesToPropagate)) {
            $this->propertyAccessor->setValue($menuUpdate, 'titles', $titlesToPropagate);
        }

        if ($titles?->count()) {
            return;
        }

        if (!$menuItem->getExtra(MenuUpdateInterface::IS_TRANSLATE_DISABLED)) {
            $this->menuUpdateHelper
                ->applyLocalizedFallbackValue($menuUpdate, $menuItem->getLabel(), 'title', 'string');
        } else {
            $menuUpdate->setDefaultTitle($menuItem->getLabel());
        }
    }
}
