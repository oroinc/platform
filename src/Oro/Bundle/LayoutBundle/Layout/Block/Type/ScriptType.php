<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class ScriptType extends AbstractType
{
    const NAME = 'script';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(['type' => 'text/javascript'])
            ->setDefined(['content', 'src', 'async', 'defer', 'crossorigin']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['content']      = isset($options['content']) ? $options['content'] : '';
        $view->vars['attr']['type'] = $options['type'];
        if (!empty($options['src'])) {
            $view->vars['attr']['src'] = $options['src'];
        }
        if (!empty($options['async'])) {
            $view->vars['attr']['async'] = $options['async'];
        }
        if (!empty($options['defer'])) {
            $view->vars['attr']['defer'] = $options['defer'];
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
        if (isset($view->vars['attr']['async'])) {
            if ($view->vars['attr']['async']) {
                $view->vars['attr']['async'] = 'async';
            } else {
                unset($view->vars['attr']['async']);
            }
        }
        if (isset($view->vars['attr']['defer'])) {
            if ($view->vars['attr']['defer']) {
                $view->vars['attr']['defer'] = 'defer';
            } else {
                unset($view->vars['attr']['defer']);
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
