<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

use Oro\Bundle\DataAuditBundle\Model\MenuAuditObject;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

/**
 * The audit object of an item of one kind of menu: the class of the level the item is customized at, taken
 * from the levels that kind of menu has.
 */
class MenuAuditObjectProvider implements MenuAuditObjectProviderInterface
{
    public function __construct(
        private readonly MenuAuditLevelProvider $levelProvider,
        private readonly string $menuUpdateClass
    ) {
    }

    #[\Override]
    public function getAuditObject(MenuUpdateInterface $menuUpdate): ?MenuAuditObject
    {
        $scope = $menuUpdate->getScope();
        if (null === $scope || !is_a($menuUpdate, $this->menuUpdateClass)) {
            return null;
        }

        return new MenuAuditObject(
            $this->levelProvider->getClassForScope($scope),
            (string)$menuUpdate->getMenu(),
            $scope->getId(),
            (string)$menuUpdate->getKey()
        );
    }
}
