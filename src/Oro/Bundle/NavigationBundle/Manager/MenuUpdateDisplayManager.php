<?php

namespace Oro\Bundle\NavigationBundle\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate\MenuItemToMenuUpdatePropagatorInterface;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * The manager to manipulate menu items visibility using menu updates.
 */
class MenuUpdateDisplayManager
{
    private ManagerRegistry $managerRegistry;

    private MenuUpdateManager $menuUpdateManager;

    private string $menuUpdateClass;

    public function __construct(
        ManagerRegistry $managerRegistry,
        MenuUpdateManager $menuUpdateManager,
        string $menuUpdateClass
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->menuUpdateManager = $menuUpdateManager;
        $this->menuUpdateClass = $menuUpdateClass;
    }

    public function showMenuItem(ItemInterface $menu, string $key, Scope $scope): void
    {
        $item = MenuUpdateUtils::findMenuItem($menu, $key);
        if ($item !== null) {
            $update = $this->menuUpdateManager
                ->findOrCreateMenuUpdate($menu, $scope, [
                    'key' => $item->getName(),
                    'propagationStrategy' => MenuItemToMenuUpdatePropagatorInterface::STRATEGY_BASIC,
                ]);
            $update->setActive(true);

            $entityManager = $this->getEntityManager();
            $entityManager->persist($update);

            $this->showMenuItemParents($menu, $item, $scope);
            $this->showMenuItemChildren($menu, $item, $scope);

            $entityManager->flush();
        }
    }

    private function showMenuItemParents(ItemInterface $menu, ItemInterface $item, Scope $scope): void
    {
        $parent = $item->getParent();
        if ($parent !== null && !$parent->isDisplayed()) {
            $update = $this->menuUpdateManager
                ->findOrCreateMenuUpdate($menu, $scope, [
                    'key' => $parent->getName(),
                    'propagationStrategy' => MenuItemToMenuUpdatePropagatorInterface::STRATEGY_BASIC,
                ]);
            $update->setActive(true);
            $this->getEntityManager()->persist($update);

            $this->showMenuItemParents($menu, $parent, $scope);
        }
    }

    private function showMenuItemChildren(ItemInterface $menu, ItemInterface $menuItem, Scope $scope): void
    {
        foreach ($menuItem->getChildren() as $child) {
            $update = $this->menuUpdateManager
                ->findOrCreateMenuUpdate($menu, $scope, [
                    'key' => $child->getName(),
                    'propagationStrategy' => MenuItemToMenuUpdatePropagatorInterface::STRATEGY_BASIC,
                ]);
            $update->setActive(true);
            $this->getEntityManager()->persist($update);

            $this->showMenuItemChildren($menu, $child, $scope);
        }
    }

    public function hideMenuItem(ItemInterface $menu, string $key, Scope $scope): void
    {
        $item = MenuUpdateUtils::findMenuItem($menu, $key);
        if ($item !== null) {
            $update = $this->menuUpdateManager
                ->findOrCreateMenuUpdate(
                    $menu,
                    $scope,
                    [
                        'key' => $item->getName(),
                        'propagationStrategy' => MenuItemToMenuUpdatePropagatorInterface::STRATEGY_BASIC,
                    ]
                );
            $update->setActive(false);

            $entityManager = $this->getEntityManager();
            $entityManager->persist($update);

            $this->hideMenuItemChildren($menu, $item, $scope);

            $entityManager->flush();
        }
    }

    private function hideMenuItemChildren(ItemInterface $menu, ItemInterface $item, Scope $scope): void
    {
        foreach ($item->getChildren() as $child) {
            $update = $this->menuUpdateManager->findOrCreateMenuUpdate(
                $menu,
                $scope,
                [
                    'key' => $child->getName(),
                    'propagationStrategy' => MenuItemToMenuUpdatePropagatorInterface::STRATEGY_BASIC,
                ]
            );
            $update->setActive(false);
            $this->getEntityManager()->persist($update);

            $this->hideMenuItemChildren($menu, $child, $scope);
        }
    }

    private function getEntityManager(): EntityManager
    {
        return $this->managerRegistry->getManagerForClass($this->menuUpdateClass);
    }
}
