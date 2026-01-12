<?php

namespace Oro\Component\Layout\Extension\Core;

use Oro\Component\Layout\Block\Extension as TypeExtension;
use Oro\Component\Layout\Block\Type;
use Oro\Component\Layout\Extension\AbstractExtension;

/**
 * Provides core block types and extensions for the layout system.
 *
 * This extension registers the base block types ({@see BaseType} and {@see ContainerType})
 * and the {@see ClassAttributeExtension} that are fundamental to the layout system's operation.
 */
class CoreExtension extends AbstractExtension
{
    #[\Override]
    protected function loadTypes()
    {
        return [
            new Type\BaseType(),
            new Type\ContainerType()
        ];
    }

    #[\Override]
    protected function loadTypeExtensions()
    {
        return [
            new TypeExtension\ClassAttributeExtension()
        ];
    }
}
