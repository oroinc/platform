<?php

namespace Oro\Component\Layout;

/**
 * Provides an interface for builders which can be used to build {@see RawLayout}.
 */
interface RawLayoutBuilderInterface extends LayoutManipulatorInterface
{
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
