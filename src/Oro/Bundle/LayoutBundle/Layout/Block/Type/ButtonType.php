<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class ButtonType extends AbstractType
{
    const NAME = 'button';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults(
                [
                    // the type of the button
                    // supported values: 'button' or 'input'
                    'type'   => 'button',
                    // the action type of the button
                    // supported values: 'none', 'submit' or 'reset'
                    'action' => 'none'
                ]
            )
            ->setAllowedTypes(
                [
                    'type'   => 'string',
                    'action' => 'string'
                ]
            )
            ->setOptional(['name', 'value', 'text', 'icon']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['type']   = $options['type'];
        $view->vars['action'] = $options['action'];
        if (!empty($options['name'])) {
            $view->vars['name'] = $options['name'];
        }
        if (!empty($options['value'])) {
            $view->vars['value'] = $options['value'];
        }
        if (!empty($options['text'])) {
            $view->vars['text'] = $options['text'];
        }
        if (!empty($options['icon'])) {
            $view->vars['icon'] = $options['icon'];
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
