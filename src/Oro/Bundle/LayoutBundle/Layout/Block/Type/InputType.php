<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class InputType extends AbstractType
{
    const NAME = 'input';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults(
                [
                    'type' => 'text',
                    'id' => null,
                    'name' => null,
                    'value' => null,
                    'placeholder' => null,
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['type'] = $view->vars['attr']['type'] = $options['type'];

        if ($options['name']) {
            $view->vars['attr']['name'] = $options['name'];
        }
        if ($options['id']) {
            $view->vars['attr']['id'] = $options['id'];
        }
        if ($options['placeholder']) {
            $view->vars['attr']['placeholder'] = $options['placeholder'];
        }
        if ($options['value']) {
            $view->vars['attr']['value'] = $options['value'];
        }

        if ($options['type'] === 'password') {
            $view->vars['attr']['autocomplete'] = 'off';
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
