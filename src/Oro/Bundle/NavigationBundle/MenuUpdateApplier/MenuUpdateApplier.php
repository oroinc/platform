<?php

namespace Oro\Bundle\NavigationBundle\MenuUpdateApplier;

use Knp\Menu\ItemInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\MenuUpdateApplier\Model\MenuUpdatesApplyResult;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;

/**
 * Applies menu updates to a menu item including all its children.
 */
class MenuUpdateApplier implements MenuUpdateApplierInterface
{
    private LocalizationHelper $localizationHelper;

    public function __construct(LocalizationHelper $localizationHelper)
    {
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function applyMenuUpdates(
        ItemInterface $menuItem,
        array $menuUpdates,
        array $menuOptions = []
    ): MenuUpdatesApplyResult {
        /** @var array<int,MenuUpdateInterface> $notAppliedMenuUpdates */
        $appliedMenuUpdates = [];
        /** @var array<int,MenuUpdateInterface> $notAppliedMenuUpdates */
        $notAppliedMenuUpdates = [];
        /** @var array<string,array<int,MenuUpdateInterface>> $orphanMenuUpdatesByParentName */
        $orphanMenuUpdatesByParentName = [];
        /** @var array<string,ItemInterface[]> $orphanMenuItemsByParentName */
        $orphanMenuItemsByParentName = [];

        // Having flat array of all menu items simplifies accessing them by name.
        $menuItemsByName = MenuUpdateUtils::flattenMenuItem($menuItem);

        foreach ($menuUpdates as $menuUpdate) {
            if (!$this->canBeApplied($menuUpdate, $menuItemsByName)) {
                // Stores the menu update for which the menu item does not exist and cannot be created because it is
                // non-custom.
                $notAppliedMenuUpdates[$menuUpdate->getId()] = $menuUpdate;
                continue;
            }

            $targetMenuItemName = $menuUpdate->getKey();
            $parentMenuItemName = $menuUpdate->getParentKey();
            $appliedMenuUpdates[$menuUpdate->getId()] = $menuUpdate;

            $parentItemFound = true;
            if ($parentMenuItemName) {
                $parentMenuItem = $menuItemsByName[$parentMenuItemName] ?? null;
                if ($parentMenuItem === null) {
                    $parentItemFound = false;
                    $parentMenuItem = $menuItem;
                    // Stores the menu update for which the parent menu item does not exist.
                    $orphanMenuUpdatesByParentName[$parentMenuItemName][$menuUpdate->getId()] = $menuUpdate;
                }
            } else {
                $parentMenuItem = $menuItem;
            }

            if (!isset($menuItemsByName[$targetMenuItemName])) {
                $targetMenuItem = $parentMenuItem->addChild($targetMenuItemName, $menuOptions);
                $menuItemsByName[$targetMenuItemName] = $targetMenuItem;
                if (!$parentItemFound) {
                    // Stores the menu item that is created not inside its parent according to the menu update to have
                    // the ability to reposition it later.
                    $orphanMenuItemsByParentName[$parentMenuItemName][] = $targetMenuItem;
                }

                // Checks if there are orphan menu items created in previous iterations and repositions them.
                if (isset($orphanMenuItemsByParentName[$targetMenuItemName])) {
                    // Repositions orphaned menu items into the newly created parent.
                    $this->repositionMultiple($orphanMenuItemsByParentName[$targetMenuItemName], $targetMenuItem);
                    unset(
                        $orphanMenuUpdatesByParentName[$targetMenuItemName],
                        $orphanMenuItemsByParentName[$targetMenuItemName]
                    );
                }
            } else {
                $targetMenuItem = $menuItemsByName[$targetMenuItemName];

                // Repositions the menu item according to its menu update parent key.
                if ($targetMenuItem->getParent()?->getName() !== $menuUpdate->getParentKey()) {
                    $this->reposition($targetMenuItem, $parentMenuItem);
                }
            }

            $this->applyMenuUpdate($menuUpdate, $targetMenuItem);
        }

        return new MenuUpdatesApplyResult(
            $menuItem,
            $menuUpdates,
            $appliedMenuUpdates,
            $notAppliedMenuUpdates,
            array_replace([], ...array_values($orphanMenuUpdatesByParentName))
        );
    }

    private function applyMenuUpdate(MenuUpdateInterface $menuUpdate, ItemInterface $menuItem): void
    {
        if ($menuUpdate->getTitles()->count()) {
            $menuItem->setLabel((string)$this->localizationHelper->getLocalizedValue($menuUpdate->getTitles()));
        }

        if ($menuUpdate->getUri()) {
            $menuItem->setUri($menuUpdate->getUri());
        }

        $menuItem->setDisplay($menuUpdate->isActive());

        foreach ($menuUpdate->getExtras() as $key => $extra) {
            $menuItem->setExtra($key, $extra);
        }

        foreach ($menuUpdate->getLinkAttributes() as $key => $linkAttribute) {
            $menuItem->setLinkAttribute($key, $linkAttribute);
        }

        if ($menuUpdate->getDescriptions()->count()) {
            $description = (string)$this->localizationHelper->getLocalizedValue($menuUpdate->getDescriptions());
            if ($description) {
                $menuItem->setExtra('description', $description);
            }
        }
    }

    /**
     * Menu update can be applied only if it is custom (i.e. creates new menu item) or of its target menu item exists.
     *
     * @param MenuUpdateInterface $menuUpdate
     * @param array<string,ItemInterface> $menuItemsByName
     *
     * @return bool
     */
    private function canBeApplied(MenuUpdateInterface $menuUpdate, array $menuItemsByName): bool
    {
        return isset($menuItemsByName[$menuUpdate->getKey()]) || $menuUpdate->isCustom();
    }

    /**
     * @param ItemInterface[] $menuItems
     * @param ItemInterface $parentMenuItem
     */
    private function repositionMultiple(array $menuItems, ItemInterface $parentMenuItem): void
    {
        foreach ($menuItems as $orphanMenuItem) {
            $this->reposition($orphanMenuItem, $parentMenuItem);
        }
    }

    private function reposition(ItemInterface $menuItem, ItemInterface $parentMenuItem): void
    {
        $menuItem->getParent()?->removeChild($menuItem->getName());
        $parentMenuItem->addChild($menuItem);
    }
}
