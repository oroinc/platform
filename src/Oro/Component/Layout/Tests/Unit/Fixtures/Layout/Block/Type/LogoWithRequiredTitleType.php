<?php

namespace Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class LogoWithRequiredTitleType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['title']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        $view->vars['title'] = $options['title'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'logo_with_required_title';
    }
}
