<?php

namespace Oro\Bundle\UIBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class PlaceholderType extends AbstractType
{
    const NAME = 'placeholder';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(['placeholder_name'])
            ->setOptional(['placeholder_parameters']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['placeholder_name']       = $options['placeholder_name'];
        $view->vars['placeholder_parameters'] = $options['placeholder_parameters'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
