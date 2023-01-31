<?php

namespace Oro\Bundle\NavigationBundle\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate\MenuItemToMenuUpdatePropagatorInterface;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\UIBundle\Model\TreeItem;

/**
 * The manager to move menu items using menu updates.
 */
class MenuUpdateMoveManager
{
    private ManagerRegistry $managerRegistry;

    private MenuUpdateManager $menuUpdateManager;

    private MenuItemToMenuUpdatePropagatorInterface $menuItemToMenuUpdatePropagator;

    private string $menuUpdateClass;

    public function __construct(
        ManagerRegistry $managerRegistry,
        MenuUpdateManager $menuUpdateManager,
        MenuItemToMenuUpdatePropagatorInterface $menuItemToMenuUpdatePropagator,
        string $menuUpdateClass
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->menuUpdateManager = $menuUpdateManager;
        $this->menuItemToMenuUpdatePropagator = $menuItemToMenuUpdatePropagator;
        $this->menuUpdateClass = $menuUpdateClass;
    }

    /**
     * @param ItemInterface $menu
     * @param string $key
     * @param Scope $scope
     * @param string $parentKey
     * @param int $position
     *
     * @return MenuUpdateInterface[]
     */
    public function moveMenuItem(
        ItemInterface $menu,
        string $key,
        Scope $scope,
        string $parentKey,
        int $position
    ): array {
        $menuItem = MenuUpdateUtils::findMenuItem($menu, $key);
        if ($menuItem === null) {
            return [];
        }

        $parentMenuItem = MenuUpdateUtils::findMenuItem($menu, $parentKey);
        if ($parentMenuItem === null) {
            return [];
        }

        $menuItemName = $menuItem->getName();
        $children = $parentMenuItem->getChildren();
        $childrenNames = \array_keys($children);

        if ($parentMenuItem->getName() !== $menuItem->getParent()?->getName()) {
            $menuItem->getParent()?->removeChild($menuItemName);
            $parentMenuItem->addChild($menuItem);
        } else {
            $oldPosition = \array_search($menuItemName, $childrenNames, true);
            unset($childrenNames[$oldPosition]);
            $childrenNames = \array_values($childrenNames);
        }

        \array_splice($childrenNames, $position, 0, $menuItemName);

        $menuUpdates = $this->getRepository()->findMany($menu->getName(), $scope->getId(), $childrenNames);

        foreach ($childrenNames as $index => $menuItemName) {
            if (!isset($menuUpdates[$menuItemName])) {
                $menuUpdates[$menuItemName] = $this->menuUpdateManager
                    ->createMenuUpdate($menu, $scope, [
                        'key' => $menuItemName,
                        'propagationStrategy' => MenuItemToMenuUpdatePropagatorInterface::STRATEGY_BASIC,
                    ]);
            } else {
                $this->menuItemToMenuUpdatePropagator->propagateFromMenuItem(
                    $menuUpdates[$menuItemName],
                    $parentMenuItem->getChild($menuItemName),
                    MenuItemToMenuUpdatePropagatorInterface::STRATEGY_BASIC
                );
            }

            $menuUpdates[$menuItemName]->setPriority($index);
            $menuUpdates[$menuItemName]->setParentKey($parentKey);
        }

        if ($parentKey !== $menu->getName()) {
            $menuUpdates[$parentKey] = $this->menuUpdateManager
                ->findOrCreateMenuUpdate($menu, $scope, [
                    'key' => $parentKey,
                    'propagationStrategy' => MenuItemToMenuUpdatePropagatorInterface::STRATEGY_BASIC,
                ]);
        }

        return array_values($menuUpdates);
    }

    /**
     * @param ItemInterface $menu
     * @param TreeItem[] $treeItems
     * @param Scope $scope
     * @param string $parentKey
     * @param int $position
     *
     * @return MenuUpdateInterface[]
     */
    public function moveMenuItems(
        ItemInterface $menu,
        array $treeItems,
        Scope $scope,
        string $parentKey,
        int $position
    ): array {
        $parentMenuItem = MenuUpdateUtils::findMenuItem($menu, $parentKey);
        if ($parentMenuItem === null) {
            return [];
        }

        $children = $parentMenuItem->getChildren();
        $childrenNames = \array_keys($children);

        foreach ($treeItems as $treeItem) {
            $menuItem = MenuUpdateUtils::findMenuItem($menu, $treeItem->getKey());
            if ($menuItem === null) {
                continue;
            }

            if ($parentMenuItem->getName() !== $menuItem->getParent()?->getName()) {
                $menuItem->getParent()?->removeChild($treeItem->getKey());
                $parentMenuItem->addChild($menuItem);
            } else {
                $oldPosition = \array_search($treeItem->getKey(), $childrenNames, true);
                unset($childrenNames[$oldPosition]);
                $childrenNames = \array_values($childrenNames);
            }

            \array_splice($childrenNames, $position++, 0, $treeItem->getKey());
        }

        $menuUpdates = $this->getRepository()->findMany($menu->getName(), $scope->getId(), $childrenNames);

        foreach ($childrenNames as $index => $menuItemName) {
            if (!isset($menuUpdates[$menuItemName])) {
                $menuUpdates[$menuItemName] = $this->menuUpdateManager
                    ->createMenuUpdate($menu, $scope, [
                        'key' => $menuItemName,
                        'propagationStrategy' => MenuItemToMenuUpdatePropagatorInterface::STRATEGY_BASIC,
                    ]);
            } else {
                $this->menuItemToMenuUpdatePropagator->propagateFromMenuItem(
                    $menuUpdates[$menuItemName],
                    $parentMenuItem->getChild($menuItemName),
                    MenuItemToMenuUpdatePropagatorInterface::STRATEGY_BASIC
                );
            }

            $menuUpdates[$menuItemName]->setPriority($index);
            $menuUpdates[$menuItemName]->setParentKey($parentKey);
        }

        if ($parentKey !== $menu->getName()) {
            $menuUpdates[$parentKey] = $this->menuUpdateManager
                ->findOrCreateMenuUpdate($menu, $scope, [
                    'key' => $parentKey,
                    'propagationStrategy' => MenuItemToMenuUpdatePropagatorInterface::STRATEGY_BASIC,
                ]);
        }

        return array_values($menuUpdates);
    }

    private function getRepository(): EntityRepository
    {
        return $this->getEntityManager()->getRepository($this->menuUpdateClass);
    }

    private function getEntityManager(): EntityManager
    {
        return $this->managerRegistry->getManagerForClass($this->menuUpdateClass);
    }
}
