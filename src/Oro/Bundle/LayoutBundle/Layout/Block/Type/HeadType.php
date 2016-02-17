<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\OptionValueBag;
use Oro\Component\Layout\StringOptionValueBuilder;
use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class HeadType extends AbstractContainerType
{
    const NAME = 'head';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['title' => '']);
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
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        $title = $view->vars['title'];
        if ($title instanceof OptionValueBag) {
            $view->vars['title'] = $title->buildValue(new StringOptionValueBuilder());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
