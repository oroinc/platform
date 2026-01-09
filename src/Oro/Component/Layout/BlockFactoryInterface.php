<?php

namespace Oro\Component\Layout;

/**
 * Defines the contract for creating block view hierarchies from raw layouts.
 *
 * A block factory transforms a raw layout structure into a hierarchy of block view objects
 * that can be rendered, using the provided context for variable resolution.
 */
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
