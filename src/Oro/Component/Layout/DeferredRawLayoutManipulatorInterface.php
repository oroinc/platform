<?php

namespace Oro\Component\Layout;

/**
 * Provides a set of methods to manipulate the layout and apply the changes on demand
 * In additional to LayoutManipulatorInterface provides methods to manage the layout item options
 * The options related operation are available for the layout built without the block types
 *
 * NOTES: we have to re-declare all methods from {@see RawLayoutManipulatorInterface} here
 * because in other case "@return self" points to {@see RawLayoutManipulatorInterface}
 * rather than {@see DeferredRawLayoutManipulatorInterface}.
 * But it is important for a client code because this interface provides "fluent" operations.
 */
interface DeferredRawLayoutManipulatorInterface extends
    DeferredLayoutManipulatorInterface,
    RawLayoutManipulatorInterface
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
}
