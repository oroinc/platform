<?php

namespace Oro\Bundle\UIBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class RequireJsType extends AbstractType
{
    const NAME = 'require_js';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'compressed' => true,
                'modules'    => []
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['compressed'] = $options['compressed'];
        $view->vars['modules']    = $options['modules'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
