<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class StyleType extends AbstractType
{
    const NAME = 'style';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(['type' => 'text/css'])
            ->setDefined(['content', 'src', 'media', 'scoped', 'crossorigin']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['content']      = isset($options['content']) ? $options['content'] : '';
        $view->vars['attr']['type'] = $options['type'];
        if (!empty($options['src'])) {
            $view->vars['attr']['href'] = $options['src'];
        }
        if (!empty($options['media'])) {
            $view->vars['attr']['media'] = $options['media'];
        }
        if (!empty($options['scoped'])) {
            $view->vars['attr']['scoped'] = $options['scoped'];
        }
        if (!empty($options['crossorigin'])) {
            $view->vars['attr']['crossorigin'] = $options['crossorigin'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        // final check of the view vars and their modification (if required)
        // we have to do this in the finishView because only here we can be sure that
        // expressions have been evaluated (if $context.expressions_evaluate is true)
        if (isset($view->vars['attr']['scoped'])) {
            if ($view->vars['attr']['scoped']) {
                $view->vars['attr']['scoped'] = 'scoped';
            } else {
                unset($view->vars['attr']['scoped']);
            }
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
