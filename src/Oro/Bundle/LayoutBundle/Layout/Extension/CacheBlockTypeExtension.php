<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

/**
 * Layout block type extension to allow cache option for all the blocks.
 */
class CacheBlockTypeExtension extends AbstractBlockTypeExtension
{
    /**
     * {@inheritDoc}
     */
    public function getExtendedType()
    {
        return BaseType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        $view->vars['cache'] = $options->get('cache');
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('cache', null);
    }
}
