<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\Options;

/**
 * Provides common functionality for layout block type extensions.
 *
 * This base class implements the {@see BlockTypeExtensionInterface} with default no-op implementations,
 * allowing subclasses to override only the specific extension points they need to customize
 * (build, view, options configuration).
 */
abstract class AbstractBlockTypeExtension implements BlockTypeExtensionInterface
{
    #[\Override]
    public function buildBlock(BlockBuilderInterface $builder, Options $options)
    {
    }

    #[\Override]
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
    }

    #[\Override]
    public function finishView(BlockView $view, BlockInterface $block)
    {
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
    }
}
