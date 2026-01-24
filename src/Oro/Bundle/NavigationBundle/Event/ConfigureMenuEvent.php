<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a menu is being configured.
 *
 * This event allows listeners to modify menu structure by adding, removing, or editing menu items.
 * It provides access to the menu factory and the menu item being configured, enabling dynamic
 * menu customization based on application state, user permissions, or feature toggles.
 */
class ConfigureMenuEvent extends Event
{
    const EVENT_NAME = 'oro_menu.configure';

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var ItemInterface
     */
    private $menu;

    public function __construct(FactoryInterface $factory, ItemInterface $menu)
    {
        $this->factory = $factory;
        $this->menu = $menu;
    }

    /**
     * @return FactoryInterface
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @return ItemInterface
     */
    public function getMenu()
    {
        return $this->menu;
    }

    /**
     * Get event name for given menu.
     *
     * @param  string $name
     * @return string
     */
    public static function getEventName($name)
    {
        return self::EVENT_NAME . '.' . $name;
    }
}
