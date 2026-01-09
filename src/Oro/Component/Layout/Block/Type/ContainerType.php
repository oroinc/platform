<?php

namespace Oro\Component\Layout\Block\Type;

/**
 * Block type for container blocks that can hold child blocks.
 *
 * A container block is a structural block type that serves as a parent for other blocks,
 * allowing the layout to be organized hierarchically.
 */
class ContainerType extends AbstractType
{
    public const NAME = 'container';

    #[\Override]
    public function getName()
    {
        return self::NAME;
    }
}
