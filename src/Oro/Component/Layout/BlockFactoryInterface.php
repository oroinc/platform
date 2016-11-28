<?php

namespace Oro\Component\Layout;

interface BlockFactoryInterface
{
    /**
     * Creates the object represents the hierarchy of block view objects
     *
     * @param RawLayout        $rawLayout
     * @param ContextInterface $context
     *
     * @return BlockView
     */
    public function createBlockView(RawLayout $rawLayout, ContextInterface $context);
}
