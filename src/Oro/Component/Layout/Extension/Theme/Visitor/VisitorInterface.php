<?php

namespace Oro\Component\Layout\Extension\Theme\Visitor;

use Oro\Component\Layout\ContextInterface;

/**
 * Defines the contract for visiting and processing layout updates.
 *
 * Implementations of this interface traverse and potentially modify layout updates based on the current
 * layout context, allowing for context-aware transformation of layout update configurations.
 */
interface VisitorInterface
{
    public function walkUpdates(array &$updates, ContextInterface $context);
}
