<?php

namespace Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuItem;

use Knp\Menu\ItemInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate\MenuItemToMenuUpdatePropagatorInterface;

/**
 * Propagates menu update data as extra data to menu item.
 */
class ExtrasPropagator implements MenuUpdateToMenuItemPropagatorInterface
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
        $descriptions = $menuUpdate->getDescriptions();
        if ($descriptions->count()) {
            $description = (string)$this->localizationHelper->getLocalizedValue($descriptions);
            if ($description) {
                $menuItem->setExtra(MenuUpdateInterface::DESCRIPTION, $description);
            }
        }

        $menuItem->setExtra(MenuUpdateInterface::IS_DIVIDER, $menuUpdate->isDivider());
        $menuItem->setExtra(
            MenuUpdateInterface::IS_TRANSLATE_DISABLED,
            $menuUpdate->getId() && !$menuUpdate->getTitles()->isEmpty()
        );
        $menuItem->setExtra(MenuUpdateInterface::IS_CUSTOM, $menuUpdate->isCustom());
        $menuItem->setExtra(MenuUpdateInterface::IS_SYNTHETIC, $menuUpdate->isSynthetic());

        if ($menuUpdate->getPriority() !== null) {
            $menuItem->setExtra(MenuUpdateInterface::POSITION, $menuUpdate->getPriority());
        }

        if ($menuUpdate->getIcon() !== null) {
            $menuItem->setExtra(MenuUpdateInterface::ICON, $menuUpdate->getIcon());
        }
    }
}
