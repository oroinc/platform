<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * This event is triggered after create/update/delete of MenuUpdate in specified scope
 */
class MenuUpdateScopeChangeEvent extends Event
{
    const NAME = 'oro_menu.menu_update_scope_change';

    /**
     * @var string
     */
    private $menuName;

    /**
     * @var Scope
     */
    private $scope;

    /**
     * @param string $menuName
     * @param Scope $scope
     */
    public function __construct($menuName, Scope $scope)
    {
        $this->menuName = $menuName;
        $this->scope = $scope;
    }

    /**
     * @return string
     */
    public function getMenuName()
    {
        return $this->menuName;
    }

    /**
     * @return Scope
     */
    public function getScope()
    {
        return $this->scope;
    }
}
