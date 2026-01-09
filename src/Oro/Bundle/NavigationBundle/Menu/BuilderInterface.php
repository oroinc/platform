<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\ItemInterface;

/**
 * Defines the contract for menu builders.
 *
 * Implementations of this interface are responsible for constructing and modifying menu structures.
 * Menu builders are invoked during menu configuration to add, remove, or edit menu items based on
 * application logic, user permissions, or other contextual factors. Multiple builders can be chained
 * together to progressively build complex menu hierarchies.
 */
interface BuilderInterface
{
    /**
     * Modify menu by adding, removing or editing items.
     *
     * @param \Knp\Menu\ItemInterface $menu
     * @param array                   $options
     * @param string|null             $alias
     */
    public function build(ItemInterface $menu, array $options = array(), $alias = null);
}
