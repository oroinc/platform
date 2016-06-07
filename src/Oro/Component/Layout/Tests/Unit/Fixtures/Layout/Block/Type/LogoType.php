<?php

namespace Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Block\Type\AbstractType;

class LogoType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
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
