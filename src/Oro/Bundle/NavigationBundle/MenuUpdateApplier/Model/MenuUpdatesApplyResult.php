<?php

namespace Oro\Bundle\NavigationBundle\MenuUpdateApplier\Model;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

/**
 * Applies a menu update to a menu item.
 */
class MenuUpdatesApplyResult
{
    private ItemInterface $menu;

    private array $allMenuUpdates;

    private array $appliedMenuUpdates;

    private array $notAppliedMenuUpdates;

    private array $orphanMenuUpdates;

    public function __construct(
        ItemInterface $menu,
        array $allMenuUpdates,
        array $appliedMenuUpdates,
        array $notAppliedMenuUpdates,
        array $orphanMenuUpdates
    ) {
        $this->menu = $menu;
        $this->allMenuUpdates = $allMenuUpdates;
        $this->appliedMenuUpdates = $appliedMenuUpdates;
        $this->notAppliedMenuUpdates = $notAppliedMenuUpdates;
        $this->orphanMenuUpdates = $orphanMenuUpdates;
    }

    /**
     * @return ItemInterface
     */
    public function getMenu(): ItemInterface
    {
        return $this->menu;
    }

    /**
     * @return array<MenuUpdateInterface> List of MenuUpdateInterface objects.
     */
    public function getAllMenuUpdates(): array
    {
        return $this->allMenuUpdates;
    }

    /**
     * Returns applied menu updates.
     *
     * @return array<int,MenuUpdateInterface> List of MenuUpdateInterface objects indexed by menu update ID.
     */
    public function getAppliedMenuUpdates(): array
    {
        return $this->appliedMenuUpdates;
    }

    /**
     * Returns non-applied menu updates as the corresponding menu items with MenuUpdate::$key name
     * do not exist.
     *
     * @return array<int,MenuUpdateInterface> List of MenuUpdateInterface objects indexed by menu update ID.
     */
    public function getNotAppliedMenuUpdates(): array
    {
        return $this->notAppliedMenuUpdates;
    }

    /**
     * Returns applied menu updates that created menu items inside the root menu item
     * instead of their corresponding parent menu items as the parent menu items with MenuUpdate::$parentKey name
     * do not exist.
     *
     * @return array<int,MenuUpdateInterface> List of MenuUpdateInterface objects indexed by menu update ID.
     */
    public function getOrphanMenuUpdates(): array
    {
        return $this->orphanMenuUpdates;
    }
}
