<?php

namespace Oro\Bundle\NavigationBundle\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Exception\LogicException;
use Oro\Bundle\NavigationBundle\MenuUpdate\Factory\MenuUpdateFactoryInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate\MenuItemToMenuUpdatePropagatorInterface;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * The manager to manipulate menu items using menu updates
 */
class MenuUpdateManager
{
    private ManagerRegistry $managerRegistry;

    private MenuUpdateFactoryInterface $menuUpdateFactory;

    private MenuItemToMenuUpdatePropagatorInterface $menuItemToMenuUpdatePropagator;

    private string $menuUpdateClass;

    private string $scopeType;

    public function __construct(
        ManagerRegistry $managerRegistry,
        MenuUpdateFactoryInterface $menuUpdateFactory,
        MenuItemToMenuUpdatePropagatorInterface $menuItemToMenuUpdatePropagator,
        string $menuUpdateClass,
        string $scopeType
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->menuUpdateFactory = $menuUpdateFactory;
        $this->menuItemToMenuUpdatePropagator = $menuItemToMenuUpdatePropagator;
        $this->menuUpdateClass = $menuUpdateClass;
        $this->scopeType = $scopeType;
    }

    /**
     * @param ItemInterface $menu
     * @param array $options Arbitrary options to take into account when creating a menu update.
     *                       {@see MenuUpdateFactoryInterface}
     *  [
     *      ?'key' => string, // Menu item propagation strategy. Default is
     *      ?'propagationStrategy' => string, // Menu item propagation strategy. Default is
     *                                        // MenuItemToMenuUpdatePropagatorInterface::STRATEGY_FULL.
     *      // ... other available fields of the menu update.
     *  ]
     *
     * @return MenuUpdateInterface
     */
    public function createMenuUpdate(ItemInterface $menu, Scope $scope, array $options = []): MenuUpdateInterface
    {
        $propagationStrategy = $options['propagationStrategy']
            ?? MenuItemToMenuUpdatePropagatorInterface::STRATEGY_FULL;
        unset($options['propagationStrategy']);

        $menuItem = MenuUpdateUtils::findMenuItem($menu, $options['key'] ?? null);
        $options += ['custom' => $menuItem === null];

        if ($menuItem === null) {
            $menuItemParent = MenuUpdateUtils::findMenuItem($menu, $options['parentKey'] ?? null) ?? $menu;
            $options += ['priority' => count($menuItemParent->getChildren())];
        }

        $menuUpdate = $this->menuUpdateFactory->createMenuUpdate($menu->getName(), $scope, $options);
        if ($menuItem !== null) {
            $this->menuItemToMenuUpdatePropagator->propagateFromMenuItem($menuUpdate, $menuItem, $propagationStrategy);
        }

        return $menuUpdate;
    }

    public function findMenuUpdate(string $menuName, string $key, Scope $scope): ?MenuUpdateInterface
    {
        if (null === $scope->getId()) {
            return null;
        }

        return $this->getRepository()->findOneBy(
            [
                'menu' => $menuName,
                'key' => $key,
                'scope' => $scope,
            ]
        );
    }

    /**
     * @param ItemInterface $menu
     * @param array $options Arbitrary options to take into account when creating a menu update.
     *                       {@see MenuUpdateFactoryInterface}
     *  [
     *      'key' => string, // Menu item name of the menu update to search or create.
     *      'scope' => Scope, // Scope related to the menu update.
     *      ?'propagationStrategy' => string, // Menu item propagation strategy to apply. Default is
     *                                        // MenuItemToMenuUpdatePropagatorInterface::STRATEGY_FULL.
     *      // ...
     *  ]
     *
     * @return MenuUpdateInterface
     */
    public function findOrCreateMenuUpdate(ItemInterface $menu, Scope $scope, array $options): MenuUpdateInterface
    {
        $key = $options['key'] ?? null;
        if (!is_scalar($key)) {
            throw new LogicException(
                sprintf('The option "key" with value "%s" is expected to be of type "scalar"', get_debug_type($key))
            );
        }

        $propagationStrategy = $options['propagationStrategy'] ??
            MenuItemToMenuUpdatePropagatorInterface::STRATEGY_FULL;

        $menuUpdate = $this->findMenuUpdate($menu->getName(), $key, $scope);
        $menuItem = MenuUpdateUtils::findMenuItem($menu, $key);

        if ($menuUpdate === null) {
            $menuUpdate = $this->createMenuUpdate($menu, $scope, $options);
        } elseif ($menuItem !== null) {
            $this->menuItemToMenuUpdatePropagator->propagateFromMenuItem($menuUpdate, $menuItem, $propagationStrategy);
        }

        return $menuUpdate;
    }

    public function deleteMenuUpdates(Scope $scope, ?string $menuName = null): void
    {
        $criteria['scope'] = $scope;

        if ($menuName) {
            $criteria['menu'] = $menuName;
        }

        $menuUpdates = $this->getRepository()->findBy($criteria);
        $entityManager = $this->getEntityManager();

        foreach ($menuUpdates as $menuUpdate) {
            $entityManager->remove($menuUpdate);
        }

        $entityManager->flush($menuUpdates);
    }

    public function getRepository(): EntityRepository
    {
        return $this->getEntityManager()->getRepository($this->menuUpdateClass);
    }

    private function getEntityManager(): EntityManager
    {
        return $this->managerRegistry->getManagerForClass($this->menuUpdateClass);
    }

    public function getEntityClass(): string
    {
        return $this->menuUpdateClass;
    }

    public function getScopeType(): string
    {
        return $this->scopeType;
    }
}
