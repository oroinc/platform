<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Util\BlockUtils;

class InputType extends AbstractType
{
    const NAME = 'input';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'type' => 'text'
            ]
        );
        $resolver->setDefined(
            [
                'id',
                'name',
                'value',
                'placeholder'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        BlockUtils::setViewVarsFromOptions($view, $options, ['name', 'type', 'value', 'placeholder']);
        if (isset($options['id'])) {
            $view->vars['attr']['id'] = $options->get('id', false);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block)
    {
        if (!$view->vars['name']) {
            unset($view->vars['name']);
        }
        if (!$view->vars['value']) {
            unset($view->vars['value']);
        }
        if (!$view->vars['placeholder']) {
            unset($view->vars['placeholder']);
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
