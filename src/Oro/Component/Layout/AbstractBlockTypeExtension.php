<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;

abstract class AbstractBlockTypeExtension implements BlockTypeExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildBlock(BlockBuilderInterface $builder, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeOptions(array &$options, ContextInterface $context, DataAccessorInterface $data)
    {

    }
}
