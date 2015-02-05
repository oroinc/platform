<?php

namespace Oro\Component\Layout;

/**
 * Interface for extensions which provide block types, block type extensions and layout updates.
 */
interface ExtensionInterface
{
    /**
     * Returns a block type by name.
     *
     * @param string $name The name of the block type
     *
     * @return BlockTypeInterface
     *
     * @throws Exception\InvalidArgumentException if the given type is not supported by this extension
     */
    public function getBlockType($name);

    /**
     * Checks whether the given block type is supported.
     *
     * @param string $name The name of the block type
     *
     * @return bool true, if the given type is supported by this extension; otherwise, false
     */
    public function hasBlockType($name);

    /**
     * Returns extensions for the given block type.
     *
     * @param string $name The name of the block type
     *
     * @return BlockTypeExtensionInterface[]
     */
    public function getBlockTypeExtensions($name);

    /**
     * Checks whether this extension provides extensions for the given block type.
     *
     * @param string $name The name of the block type
     *
     * @return bool true, if the given block type has extensions; otherwise, false
     */
    public function hasBlockTypeExtensions($name);

    /**
     * Returns layout updates for the given layout item.
     *
     * @param string $id The id of the layout item
     *
     * @return LayoutUpdateInterface[]
     */
    public function getLayoutUpdates($id);

    /**
     * Checks whether this extension provides layout updates for the given layout item.
     *
     * @param string $id The id of the layout item
     *
     * @return bool true, if the given layout item has additional layout updates; otherwise, false
     */
    public function hasLayoutUpdates($id);
}
