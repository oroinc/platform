<?php

namespace Oro\Component\Layout;

interface BlockInterface
{
    /**
     * Returns the id of the block
     *
     * @return string
     */
    public function getId();

    /**
     * Returns the name of the block type
     *
     * @return string
     */
    public function getName();

    /**
     * Returns a list of all aliases registered for the block
     *
     * @return string[] The aliases
     */
    public function getAliases();

    /**
     * Returns the parent block
     *
     * @return BlockInterface|null The parent block
     */
    public function getParent();

    /**
     * Returns block options
     *
     * @return array
     */
    public function getOptions();

    /**
     * Returns the execution context
     *
     * @return ContextInterface
     */
    public function getContext();
}
