<?php

namespace Oro\Bundle\NavigationBundle\MenuUpdate\Factory;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\JsTree\MenuUpdateTreeHandler;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Creates an instance of {@see MenuUpdateInterface} using $menuUpdateClass.
 */
class MenuUpdateFactory implements MenuUpdateFactoryInterface
{
    private PropertyAccessorInterface $propertyAccessor;

    private string $menuUpdateClass;

    public function __construct(PropertyAccessorInterface $propertyAccessor, string $menuUpdateClass)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->menuUpdateClass = $menuUpdateClass;
    }

    /**
     * @param string $menuName
     * @param Scope $scope
     * @param array $options
     *  [
     *      ?'key' => string, // Menu item name to apply the menu update to.
     *      ?'parentKey' => string, // Parent name of the menu item to apply the menu update to.
     *      ?'divider' => bool, // Whether menu update should be marked as divider.
     *      // ... other available fields of the menu update.
     *  ]
     *
     * @return MenuUpdateInterface
     */
    public function createMenuUpdate(string $menuName, Scope $scope, array $options = []): MenuUpdateInterface
    {
        /** @var MenuUpdateInterface $menuUpdate */
        $menuUpdate = new $this->menuUpdateClass;
        $menuUpdate->setMenu($menuName);
        $menuUpdate->setScope($scope);

        if (!isset($options['key'])) {
            $menuUpdate->generateKey();
        }

        if (isset($options['divider']) && $options['divider']) {
            $menuUpdate->setDivider(true);
            $menuUpdate->setDefaultTitle(MenuUpdateTreeHandler::MENU_ITEM_DIVIDER_LABEL);
            $menuUpdate->setUri('#');
        }

        foreach ($options as $key => $value) {
            if ($this->propertyAccessor->isWritable($menuUpdate, $key)) {
                $this->propertyAccessor->setValue($menuUpdate, $key, $value);
            }
        }

        return $menuUpdate;
    }
}
