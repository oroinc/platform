<?php

namespace Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Propagates menu item extras options to menu update.
 */
class ExtrasPropagator implements MenuItemToMenuUpdatePropagatorInterface
{
    private PropertyAccessorInterface $propertyAccessor;

    private array $mapping = ['position' => 'priority'];

    private array $excludeKeys = [];

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    public function setMapping(array $extrasMapping): void
    {
        $this->mapping = $extrasMapping;
    }

    public function setExcludeKeys(array $excludeKeys): void
    {
        $this->excludeKeys = $excludeKeys;
    }

    public function isApplicable(MenuUpdateInterface $menuUpdate, ItemInterface $menuItem, string $strategy): bool
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

    public function propagateFromMenuItem(
        MenuUpdateInterface $menuUpdate,
        ItemInterface $menuItem,
        string $strategy
    ): void {
        foreach ($menuItem->getExtras() as $key => $value) {
            if (in_array($key, $this->excludeKeys, true)) {
                continue;
            }

            if (array_key_exists($key, $this->mapping)) {
                $key = $this->mapping[$key];
            }

            $this->setMenuUpdateFieldValue($menuUpdate, $key, $value);
        }

        if ($menuUpdate->getPriority() === null) {
            $parent = $menuItem->getParent();
            if ($parent !== null) {
                $menuUpdate->setPriority(
                    array_search($menuItem->getName(), array_keys($parent->getChildren()), true)
                );
            }
        }
    }

    private function setMenuUpdateFieldValue(MenuUpdateInterface $menuUpdate, string $key, mixed $value): void
    {
        if ($this->propertyAccessor->isWritable($menuUpdate, $key)) {
            $currentValue = $this->propertyAccessor->getValue($menuUpdate, $key);
            if ($currentValue === null || is_bool($currentValue)) {
                $this->propertyAccessor->setValue($menuUpdate, $key, $value);
            }
        }
    }
}
