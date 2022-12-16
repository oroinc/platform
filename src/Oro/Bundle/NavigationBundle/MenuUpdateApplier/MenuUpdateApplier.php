<?php

namespace Oro\Bundle\NavigationBundle\MenuUpdateApplier;

use Knp\Menu\ItemInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\MenuUpdateApplier\Model\MenuUpdatesApplyResult;
use Oro\Bundle\NavigationBundle\Utils\LostItemsManipulator;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;

/**
 * Applies menu updates to a menu item including all its children.
 *
 * Menu updates for custom menu items for which parent menu item does not exist are
 * put to special menu item - "lost items container".
 * Menu updates for non-custom menu items that do not exist are put to special menu item - "lost items container".
 */
class MenuUpdateApplier implements MenuUpdateApplierInterface
{
    public const IS_CUSTOM = 'is_custom';

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
        /** @var array<int,MenuUpdateInterface> $appliedMenuUpdates */
        $appliedMenuUpdates = [];
        /** @var array<string,array<int,MenuUpdateInterface>> $lostMenuUpdatesByParentName */
        $lostMenuUpdatesByParentName = [];
        /** @var array<string,ItemInterface[]> $lostMenuItemsByParentName */
        $lostMenuItemsByParentName = [];

        // Having a flat array of all menu items simplifies accessing them by name.
        $menuItemsByName = MenuUpdateUtils::flattenMenuItem($menuItem);

        /** @var ItemInterface $lostItemsContainer */
        $lostItemsContainer = LostItemsManipulator::getLostItemsContainer($menuItem);

        $menuName = $menuItem->getRoot()->getName();

        foreach ($menuUpdates as $menuUpdate) {
            $targetMenuItemName = $menuUpdate->getKey();
            if ($targetMenuItemName === $lostItemsContainer->getName()) {
                continue;
            }

            $parentMenuItemName = $menuUpdate->getParentKey() ?? $menuName;

            if (!$this->canBeApplied($menuUpdate, $menuItemsByName)) {
                $targetMenuItem = $lostItemsContainer->addChild($targetMenuItemName, $menuOptions);
                $this->applyMenuUpdate($menuUpdate, $targetMenuItem);
                $lostMenuUpdatesByParentName[$parentMenuItemName][$menuUpdate->getId()] = $menuUpdate;

                continue;
            }

            $appliedMenuUpdates[$menuUpdate->getId()] = $menuUpdate;

            $parentItemFound = true;
            $parentMenuItem = $menuItemsByName[$parentMenuItemName] ?? null;
            if ($parentMenuItem === null) {
                $parentItemFound = false;
                $parentMenuItem = $lostItemsContainer;
                // Stores the menu update for which the parent menu item does not exist.
                $lostMenuUpdatesByParentName[$parentMenuItemName][$menuUpdate->getId()] = $menuUpdate;
            }

            if (!isset($menuItemsByName[$targetMenuItemName])) {
                $targetMenuItem = $parentMenuItem->addChild($targetMenuItemName, $menuOptions);
                $menuItemsByName[$targetMenuItemName] = $targetMenuItem;
                if (!$parentItemFound) {
                    // Stores the menu item that is created not inside its parent according to the menu update to have
                    // the ability to reposition it later.
                    $lostMenuItemsByParentName[$parentMenuItemName][] = $targetMenuItem;
                }

                // Checks if there are lost menu items created in previous iterations and repositions them.
                if (isset($lostMenuItemsByParentName[$targetMenuItemName])) {
                    // Repositions lost menu items into the newly created parent.
                    $this->repositionMultiple($lostMenuItemsByParentName[$targetMenuItemName], $targetMenuItem);
                    unset(
                        $lostMenuUpdatesByParentName[$targetMenuItemName],
                        $lostMenuItemsByParentName[$targetMenuItemName]
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

        // Moves lost items container to the end of menu so the lost items can be processed after the regular ones
        // by other menu builders.
        $this->moveLostItemsContainer($lostItemsContainer);

        return new MenuUpdatesApplyResult(
            $menuItem,
            $menuUpdates,
            $appliedMenuUpdates,
            array_replace([], ...array_values($lostMenuUpdatesByParentName))
        );
    }

    private function moveLostItemsContainer(ItemInterface $lostItemsContainer): void
    {
        // Moves lost items container to the end of menu so the lost items can be processed after the regular ones
        // by other menu builders.
        $menu = $lostItemsContainer->getParent();
        $menu?->removeChild($lostItemsContainer);
        if ($lostItemsContainer->hasChildren()) {
            $menu?->addChild($lostItemsContainer);
        }
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

        // Stores the name of the parent menu item to make it possible to move it later if needed.
        $menuItem->setExtra(
            LostItemsManipulator::IMPLIED_PARENT_NAME,
            $menuUpdate->getParentKey() ?? $menuUpdate->getMenu()
        );

        // Flags menu item as custom.
        $menuItem->setExtra(self::IS_CUSTOM, $menuUpdate->isCustom());

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
        foreach ($menuItems as $menuItem) {
            $this->reposition($menuItem, $parentMenuItem);
        }
    }

    private function reposition(ItemInterface $menuItem, ItemInterface $parentMenuItem): void
    {
        $menuItem->getParent()?->removeChild($menuItem->getName());
        $parentMenuItem->addChild($menuItem);
    }
}
