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
     * Returns the execution context
     *
     * @return ContextInterface
     */
    public function getContext();
}
