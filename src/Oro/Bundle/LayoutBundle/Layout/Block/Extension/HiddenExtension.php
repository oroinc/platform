<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Extension;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

/**
 * Adds 'hidden' option for all block types.
 * This option is optional and defaults to FALSE.
 */
class HiddenExtension extends AbstractBlockTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['hidden']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['hidden'] = isset($options['hidden']) ? $options['hidden'] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return BaseType::NAME;
    }
}
