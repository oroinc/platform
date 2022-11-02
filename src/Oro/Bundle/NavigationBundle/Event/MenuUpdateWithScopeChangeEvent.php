<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is triggered after create/update/delete of MenuUpdate in specified scope
 */
class MenuUpdateWithScopeChangeEvent extends Event
{
    const NAME = 'oro_menu.menu_update_with_scope_change';

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
