<?php

namespace Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuItem;

use Knp\Menu\ItemInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate\MenuItemToMenuUpdatePropagatorInterface;

/**
 * Propagates menu update basic data to menu item.
 */
class BasicPropagator implements MenuUpdateToMenuItemPropagatorInterface
{
    private LocalizationHelper $localizationHelper;

    public function __construct(LocalizationHelper $localizationHelper)
    {
        $this->localizationHelper = $localizationHelper;
    }

    public function isApplicable(ItemInterface $menuItem, MenuUpdateInterface $menuUpdate, string $strategy): bool
    {
        return in_array(
            $strategy,
            [
                MenuItemToMenuUpdatePropagatorInterface::STRATEGY_BASIC,
                MenuItemToMenuUpdatePropagatorInterface::STRATEGY_FULL
            ],
            true
        );
    }

    public function propagateFromMenuUpdate(
        ItemInterface $menuItem,
        MenuUpdateInterface $menuUpdate,
        string $strategy
    ): void {
        $titles = $menuUpdate->getTitles();
        if ($titles->count()) {
            $menuItem->setLabel((string)$this->localizationHelper->getLocalizedValue($titles));
        }

        if ($menuUpdate->getUri()) {
            $menuItem->setUri($menuUpdate->getUri());
        }

        $menuItem->setDisplay($menuUpdate->isActive());

        foreach ($menuUpdate->getLinkAttributes() as $key => $linkAttribute) {
            $menuItem->setLinkAttribute($key, $linkAttribute);
        }
    }
}
