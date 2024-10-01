<?php

namespace Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class LogoType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'title' => ''
            ]
        );
    }

    #[\Override]
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        $view->vars['title'] = $options['title'];
    }

    #[\Override]
    public function getName()
    {
        return 'logo';
    }
}
