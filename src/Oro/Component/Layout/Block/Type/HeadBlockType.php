<?php

namespace Oro\Component\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\AbstractBlockType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class HeadBlockType extends AbstractBlockType
{
    /**
    * {@inheritdoc}
    */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'title' => ''
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars = array_replace($view->vars, [
            'title' => $options['title']
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'container';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'head';
    }
}
