<?php

namespace Oro\Component\Layout;

/**
 * Provides an interface for builders which can be used to build {@see RawLayout}.
 *
 * NOTES: we have to re-declare all methods from {@see LayoutManipulatorInterface} here
 * because in other case "@return self" points to {@see LayoutManipulatorInterface}
 * rather than {@see RawLayoutBuilderInterface}.
 * But it is important for a client code because this interface provides "fluent" operations.
 */
interface RawLayoutBuilderInterface extends LayoutManipulatorInterface
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
     * Checks whether the item with the given id exists in the layout
     *
     * @param string $id The item id
     *
     * @return bool
     */
    public function has($id);

    /**
     * Checks whether the given item alias exists
     *
     * @param string $alias The item alias
     *
     * @return bool
     */
    public function hasAlias($alias);

    /**
     * Returns all options for the given layout item
     *
     * @param string $id The item id
     *
     * @return array
     */
    public function getOptions($id);

    /**
     * Returns the built layout data
     *
     * @return RawLayout
     */
    public function getRawLayout();
}
