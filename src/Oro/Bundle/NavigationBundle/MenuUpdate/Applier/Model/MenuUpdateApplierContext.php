<?php

namespace Oro\Bundle\NavigationBundle\MenuUpdate\Applier\Model;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;

/**
 * Context for menu update appliers.
 */
class MenuUpdateApplierContext
{
    private ItemInterface $menu;

    /**
     * @var array<string,ItemInterface>
     */
    private array $menuItemsByName = [];

    /**
     * @var array<string,ItemInterface>
     */
    private array $createdItems = [];

    /**
     * @var array<string,array<int,MenuUpdateInterface>>
     */
    private array $createdItemsMenuUpdates = [];

    /**
     * @var array<string,ItemInterface>
     */
    private array $updatedItems = [];

    /**
     * @var array<string,array<int,MenuUpdateInterface>>
     */
    private array $updatedItemsMenuUpdates = [];

    /**
     * @var array<string,array<string,ItemInterface>>
     */
    private array $orphanedItemsByParentName = [];

    /**
     * @var array<string,array<int,MenuUpdateInterface>>
     */
    private array $orphanedItemsMenuUpdatesByParentName = [];

    /**
     * @var array<string,ItemInterface>
     */
    private array $lostItems = [];

    /**
     * @var array<string,array<int,MenuUpdateInterface>>
     */
    private array $lostItemsMenuUpdates = [];

    public function __construct(ItemInterface $menu)
    {
        $this->menu = $menu;
    }

    /**
     * @return ItemInterface
     */
    public function getMenu(): ItemInterface
    {
        return $this->menu;
    }

    public function getMenuItemsByName(): array
    {
        $this->initMenuItems();

        return $this->menuItemsByName;
    }

    public function getMenuItemByName(string $name): ?ItemInterface
    {
        $this->initMenuItems();

        return $this->menuItemsByName[$name] ?? null;
    }

    private function initMenuItems(): void
    {
        if (count($this->menuItemsByName) === 0) {
            $this->menuItemsByName = MenuUpdateUtils::flattenMenuItem($this->menu);
        }
    }

    /**
     * @return array<string,ItemInterface>
     */
    public function getCreatedItems(): array
    {
        return $this->createdItems;
    }

    /**
     * @return array<string,array<int,MenuUpdateInterface>>
     */
    public function getCreatedItemsMenuUpdates(): array
    {
        return $this->createdItemsMenuUpdates;
    }

    public function addCreatedItem(ItemInterface $menuItem, MenuUpdateInterface $menuUpdate): self
    {
        $this->initMenuItems();

        $this->createdItems[$menuItem->getName()] = $menuItem;
        $this->createdItemsMenuUpdates[$menuItem->getName()][$menuUpdate->getId()] = $menuUpdate;
        $this->menuItemsByName[$menuItem->getName()] = $menuItem;

        return $this;
    }

    public function isCreatedItem(string $menuItemName): bool
    {
        return isset($this->createdItems[$menuItemName]);
    }

    /**
     * @return array<string,ItemInterface>
     */
    public function getUpdatedItems(): array
    {
        return $this->updatedItems;
    }

    /**
     * @return array<string,array<int,MenuUpdateInterface>>
     */
    public function getUpdatedItemsMenuUpdates(): array
    {
        return $this->updatedItemsMenuUpdates;
    }

    public function addUpdatedItem(ItemInterface $menuItem, MenuUpdateInterface $menuUpdate): self
    {
        $this->updatedItems[$menuItem->getName()] = $menuItem;
        $this->updatedItemsMenuUpdates[$menuItem->getName()][$menuUpdate->getId()] = $menuUpdate;

        return $this;
    }

    public function isUpdatedItem(string $menuItemName): bool
    {
        return isset($this->updatedItems[$menuItemName]);
    }

    /**
     * @return array<string,ItemInterface>
     */
    public function getOrphanedItems(?string $parentMenuItemName = null): array
    {
        if ($parentMenuItemName !== null) {
            return $this->orphanedItemsByParentName[$parentMenuItemName] ?? [];
        }

        return $this->orphanedItemsByParentName;
    }

    /**
     * @return array<string,ItemInterface>
     */
    public function getOrphanedItemsMenuUpdates(?string $parentMenuItemName = null): array
    {
        if ($parentMenuItemName !== null) {
            return $this->orphanedItemsMenuUpdatesByParentName[$parentMenuItemName] ?? [];
        }

        return $this->orphanedItemsMenuUpdatesByParentName;
    }

    public function addOrphanedItem(
        string $parentMenuItemName,
        ItemInterface $menuItem,
        MenuUpdateInterface $menuUpdate
    ): self {
        $name = $menuItem->getName();
        $this->orphanedItemsByParentName[$parentMenuItemName][$name] = $menuItem;
        $this->orphanedItemsMenuUpdatesByParentName[$parentMenuItemName][$name][$menuUpdate->getId()] = $menuUpdate;

        return $this;
    }

    public function removeOrphanedItems(string $parentMenuItemName): self
    {
        unset(
            $this->orphanedItemsByParentName[$parentMenuItemName],
            $this->orphanedItemsMenuUpdatesByParentName[$parentMenuItemName]
        );


        return $this;
    }

    public function removeOrphanedItem(string $parentMenuItemName, string $menuItemName): self
    {
        unset(
            $this->orphanedItemsByParentName[$parentMenuItemName][$menuItemName],
            $this->orphanedItemsMenuUpdatesByParentName[$parentMenuItemName]
        );

        return $this;
    }

    /**
     * @return array<string,ItemInterface>
     */
    public function getLostItems(): array
    {
        return $this->lostItems;
    }

    /**
     * @return array<string,array<int,MenuUpdateInterface>>
     */
    public function getLostItemsMenuUpdates(): array
    {
        return $this->lostItemsMenuUpdates;
    }

    public function addLostItem(ItemInterface $menuItem, MenuUpdateInterface $menuUpdate): self
    {
        $this->lostItems[$menuItem->getName()] = $menuItem;
        $this->lostItemsMenuUpdates[$menuItem->getName()][$menuUpdate->getId()] = $menuUpdate;

        return $this;
    }

    public function removeLostItem(string $menuItemName): self
    {
        unset($this->lostItems[$menuItemName], $this->lostItemsMenuUpdates[$menuItemName]);

        return $this;
    }

    public function isLostItem(string $menuItemName): bool
    {
        return isset($this->lostItems[$menuItemName]);
    }
}
