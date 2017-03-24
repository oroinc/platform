<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * This event is triggered after create/update/delete of MenuUpdate in specified scope
 */
class MenuUpdateChangeEvent extends Event
{
    const NAME = 'oro_menu.menu_update_change';

    /**
     * @var string
     */
    private $menuName;

    /**
     * @var array
     */
    private $context;

    /**
     * @param string $menuName
     * @param array $context
     */
    public function __construct($menuName, array $context)
    {
        $this->menuName = $menuName;
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getMenuName()
    {
        return $this->menuName;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
