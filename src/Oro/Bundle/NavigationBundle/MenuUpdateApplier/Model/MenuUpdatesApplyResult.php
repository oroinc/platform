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

    private array $lostMenuUpdates;

    public function __construct(
        ItemInterface $menu,
        array $allMenuUpdates,
        array $appliedMenuUpdates,
        array $lostMenuUpdates
    ) {
        $this->menu = $menu;
        $this->allMenuUpdates = $allMenuUpdates;
        $this->appliedMenuUpdates = $appliedMenuUpdates;
        $this->lostMenuUpdates = $lostMenuUpdates;
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
     * Returns menu updates whose target or parent menu items do not exist.
     *
     * @return array<int,MenuUpdateInterface> List of MenuUpdateInterface objects indexed by menu update ID.
     */
    public function getLostMenuUpdates(): array
    {
        return $this->lostMenuUpdates;
    }
}
