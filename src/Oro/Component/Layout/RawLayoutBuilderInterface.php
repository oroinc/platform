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
     * @param string|null               $parentId  The parent item id or alias
     * @param string|BlockTypeInterface $blockType The block type associated with the item
     * @param array                     $options   The item options
     * @param string|null               $siblingId The id or alias of an item which should be nearest neighbor
     * @param bool                      $prepend   Determines whether the moving item should be located before or after
     *                                             the specified sibling item
     *
     * @return self
     */
    public function add(
        $id,
        $parentId,
        $blockType,
        array $options = [],
        $siblingId = null,
        $prepend = false
    );

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
     * @param string $optionName  The option name or path
     * @param mixed  $optionValue The option value
     *
     * @return self
     */
    public function setOption($id, $optionName, $optionValue);

    /**
     * Adds a new value in addition to existing one for a new or existing option of the item
     *
     * @param string $id          The item id
     * @param string $optionName  The option name or path
     * @param mixed  $optionValue The option value to be added
     *
     * @return self
     */
    public function appendOption($id, $optionName, $optionValue);

    /**
     * Removes existing value from existing option of the item
     *
     * @param string $id          The item id
     * @param string $optionName  The option name or path
     * @param mixed  $optionValue The option value to be removed
     *
     * @return self
     */
    public function subtractOption($id, $optionName, $optionValue);

    /**
     * Replaces one value with another value for existing option of the item
     *
     * @param string $id             The item id
     * @param string $optionName     The option name or path
     * @param mixed  $oldOptionValue The option value to be replaced
     * @param mixed  $newOptionValue The option value to replace a value specified in $oldOptionValue parameter
     *
     * @return self
     */
    public function replaceOption($id, $optionName, $oldOptionValue, $newOptionValue);

    /**
     * Removes the option for the item
     *
     * @param string $id         The item id
     * @param string $optionName The option name or path
     *
     * @return self
     */
    public function removeOption($id, $optionName);

    /**
     * Changes the block type for the item
     *
     * @param string                    $id              The item id
     * @param string|BlockTypeInterface $blockType       The new block type associated with the item
     * @param callable|null             $optionsCallback The callback function is called before
     *                                                   the block type is changed
     *                                                   This function can be used to provide options
     *                                                   for the new block type
     *                                                   signature: function (array $options) : array
     *                                                   $options argument contains current options
     *                                                   returned array contains new options
     *
     * @return self
     */
    public function changeBlockType($id, $blockType, $optionsCallback = null);

    /**
     * Reverts the builder to the initial state
     *
     * @return self
     */
    public function clear();

    /**
     * Sets the theme(s) to be used for rendering the layout item and its children
     *
     * @param string|string[] $themes The theme(s). For example 'MyBundle:Layout:my_theme.html.twig'
     * @param string|null     $id     The id of the layout item to assign the theme(s) to
     *
     * @return self
     */
    public function setBlockTheme($themes, $id = null);

    /**
     * Sets the theme(s) to be used for rendering forms
     *
     * @param string|string[] $themes The theme(s). For example 'MyBundle:Layout:my_theme.html.twig'
     *
     * @return self
     */
    public function setFormTheme($themes);

    /**
     * Checks whether at least one item exists in the layout
     *
     * @return bool
     */
    public function isEmpty();

    /**
     * Checks whether the item with the given id exists in the layout
     *
     * @param string $id The item id
     *
     * @return bool
     */
    public function has($id);

    /**
     * Returns real id of the item
     *
     * @param string $id The id or alias of the item
     *
     * @return string The item id
     */
    public function resolveId($id);

    /**
     * Returns the id of the parent item
     *
     * @param string $id The item id
     *
     * @return string|null The id of the parent item or null if the given item is the root
     */
    public function getParentId($id);

    /**
     * Checks whether the given item really has the given parent item
     *
     * @param string $parentId The parent item id
     * @param string $id       The item id
     *
     * @return bool
     */
    public function isParentFor($parentId, $id);

    /**
     * Checks whether the given item alias exists
     *
     * @param string $alias The item alias
     *
     * @return bool
     */
    public function hasAlias($alias);

    /**
     * Returns a list of all aliases registered for the given item
     *
     * @param string $id The item id
     *
     * @return string[] The list of all registered aliases for the given item
     */
    public function getAliases($id);

    /**
     * Returns the name of the block type associated with the given layout item
     *
     * @param string $id The item id
     *
     * @return string
     */
    public function getType($id);

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
