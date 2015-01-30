<?php

namespace Oro\Component\Layout;

/**
 * In additional to LayoutStructureManipulatorInterface provides methods to manage the layout item options.
 * Options can be changed only before block types come in the game, so they cannot be changed
 * in a block type itself and block type related extensions.
 *
 * NOTES: we have to re-declare all methods from {@see LayoutStructureManipulatorInterface} here
 * because in other case "@return self" points to {@see LayoutStructureManipulatorInterface}
 * rather than {@see LayoutManipulatorInterface}.
 * But it is important for a client code because this interface provides "fluent" operations.
 *
 * If a new "fluent" methods are added to this interface do not forget to re-declare it in inherited interfaces.
 */
interface LayoutManipulatorInterface extends LayoutStructureManipulatorInterface
{
    /**
     * Adds a new item to the layout
     *
     * @param string                    $id        The item id
     * @param string                    $parentId  The parent item id or alias
     * @param string|BlockTypeInterface $blockType The block type associated with the item
     * @param array                     $options   The item options
     *
     * @return self
     */
    public function add($id, $parentId = null, $blockType = null, array $options = []);

    /**
     * Removes the item from the layout
     *
     * @param string $id The item id
     *
     * @return self
     */
    public function remove($id);

    /**
     * Moves the item to another location
     *
     * @param string      $id        The id or alias of item to be moved
     * @param string|null $parentId  The id or alias of a parent item the specified item is moved to
     *                               If this parameter is null only the order of the item is changed
     * @param string|null $siblingId The id or alias of an item which should be nearest neighbor
     * @param bool        $prepend   Determines whether the moving item should be located before or after
     *                               the specified sibling item
     *
     * @return self
     */
    public function move($id, $parentId = null, $siblingId = null, $prepend = false);

    /**
     * Creates an alias for the specified item
     *
     * @param string $alias A string that can be used instead of the item id
     * @param string $id    The item id
     *
     * @return self
     */
    public function addAlias($alias, $id);

    /**
     * Removes the item alias
     *
     * @param string $alias The item alias
     *
     * @return self
     */
    public function removeAlias($alias);

    /**
     * Adds a new option or updates a value of existing option for the item
     *
     * @param string $id          The item id
     * @param string $optionName  The option name
     * @param mixed  $optionValue The option value
     *
     * @return self
     */
    public function setOption($id, $optionName, $optionValue);

    /**
     * Removes the option for the item
     *
     * @param string $id         The item id
     * @param string $optionName The option name
     *
     * @return self
     */
    public function removeOption($id, $optionName);

    /**
     * Sets the theme(s) to be used for rendering the layout item and its children
     *
     * @param string|string[] $themes The theme(s). For example 'MyBundle:Layout:my_theme.html.twig'
     * @param string|null     $id     The id of the layout item to assign the theme(s) to
     *
     * @return self
     */
    public function setBlockTheme($themes, $id = null);
}
