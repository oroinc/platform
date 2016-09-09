<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\ArrayOptionValueBuilder;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\OptionValueBag;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class TitleType extends AbstractType
{
    const NAME = 'title';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'value' => [],
            'separator' => '',
            'reverse' => false,
            'resolve_value_bags' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        foreach (['value', 'separator', 'reverse'] as $optionName) {
            $view->vars[$optionName] = $options[$optionName];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        $title = $view->vars['value'];
        if ($title instanceof OptionValueBag) {
            $view->vars['value'] = $title->buildValue(new ArrayOptionValueBuilder(true));
        } else {
            $view->vars['value'] = (array)$view->vars['value'];
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
