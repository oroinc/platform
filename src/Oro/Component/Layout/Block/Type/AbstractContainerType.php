<?php

namespace Oro\Component\Layout\Block\Type;

/**
 * Provides common functionality for layout block types that act as containers.
 *
 * This base class extends {@see AbstractType} and sets the parent type to {@see ContainerType},
 * making it suitable for block types that contain child blocks.
 * Subclasses should implement container-specific block types.
 */
abstract class AbstractContainerType extends AbstractType
{
    #[\Override]
    public function getParent()
    {
        return ContainerType::NAME;
    }
}
