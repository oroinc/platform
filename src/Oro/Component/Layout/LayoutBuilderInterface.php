<?php

namespace Oro\Component\Layout;

interface LayoutBuilderInterface
{
    /**
     * Adds a new item to the layout
     *
     * @param string $id        The item id
     * @param string $parentId  The parent item id or alias
     * @param string $blockType The block type associated with the item
     * @param array  $options   The item options
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
     * Creates the layout
     *
     * @param string|null $rootId The id or alias of the root item
     *
     * @return Layout
     */
    public function getLayout($rootId = null);
}
