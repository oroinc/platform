<?php

namespace Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\AbstractBlockType;

class LogoType extends AbstractBlockType
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
        $view->vars['title'] = $options['title'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'logo';
    }
}
