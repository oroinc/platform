<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class FieldsetType extends AbstractContainerType
{
    const NAME = 'fieldset';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'title'            => '',
                'title_parameters' => [],
                'translatable'     => true
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['title']            = $options['title'];
        $view->vars['title_parameters'] = $options['title_parameters'];
        $view->vars['translatable']     = $options['translatable'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
