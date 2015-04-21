<?php

namespace Oro\Component\Layout;

interface BlockInterface
{
    /**
     * Returns the id of the block.
     *
     * @return string
     */
    public function getId();

    /**
     * Returns the name of the block type.
     *
     * @return string
     */
    public function getTypeName();

    /**
     * Returns a list of all aliases registered for the block.
     *
     * @return string[] The aliases
     */
    public function getAliases();

    /**
     * Returns the parent block in the layout hierarchy.
     *
     * @return BlockInterface|null The parent block
     */
    public function getParent();

    /**
     * Returns block options.
     *
     * @return array
     */
    public function getOptions();

    /**
     * Returns the block type helper.
     *
     * @return BlockTypeHelperInterface
     */
    public function getTypeHelper();

    /**
     * Returns the layout building context.
     *
     * @return ContextInterface
     */
    public function getContext();

    /**
     * Returns the data accessor.
     *
     * @return DataAccessorInterface
     */
    public function getData();
}
