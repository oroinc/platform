<?php

namespace Oro\Component\Layout;

interface LayoutItemInterface
{
    /**
     * Returns the id of the layout item
     *
     * @return string
     */
    public function getId();

    /**
     * Returns the alias of the layout item
     *
     * @return string|null
     */
    public function getAlias();

    /**
     * Returns the name of the block type
     *
     * @return string
     */
    public function getTypeName();

    /**
     * Returns layout item options
     *
     * @return array
     */
    public function getOptions();

    /**
     * Returns the id of the parent layout item
     *
     * @return string|null
     */
    public function getParentId();

    /**
     * Returns the layout building context
     *
     * @return ContextInterface
     */
    public function getContext();
}
