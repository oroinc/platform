<?php

namespace Oro\Component\Layout;

interface BlockTypeHelperInterface
{
    /**
     * Checks whether the given block type is instance of the given target block type.
     *
     * @param string|BlockTypeInterface $blockType  The block type name or instance of BlockTypeInterface
     * @param string                    $targetName The name of the block type to test with
     *
     * @return bool
     */
    public function isInstanceOf($blockType, $targetName);

    /**
     * Returns the list of all parent block type names as well as the given block type name.
     *
     * The first item in the result list is the top most type in the hierarchy, the last item is the given type.
     *
     * @param string|BlockTypeInterface $blockType The block type name or instance of BlockTypeInterface
     *
     * @return string[]
     */
    public function getTypeNames($blockType);

    /**
     * Returns the list of all parent block types as well as the given block type.
     *
     * The first item in the result list is the top most type in the hierarchy, the last item is the given type.
     *
     * @param string|BlockTypeInterface $blockType The block type name or instance of BlockTypeInterface
     *
     * @return BlockTypeInterface[]
     */
    public function getTypes($blockType);
}
