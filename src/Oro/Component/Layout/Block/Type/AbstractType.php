<?php

namespace Oro\Component\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\BlockView;

/**
 * Provides common functionality for layout block types.
 *
 * This base class implements the {@see BlockTypeInterface} with default no-op implementations
 * for build and view methods.
 * Subclasses should override specific methods to define their block type behavior, configuration, and rendering logic.
 */
abstract class AbstractType implements BlockTypeInterface
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

    #[\Override]
    public function getParent()
    {
        return BaseType::NAME;
    }
}
