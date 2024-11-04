<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\Options;

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
