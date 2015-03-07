<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Oro\Component\Layout\BlockView;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface;

class FormStartHelper
{
    /**
     * @return string[]
     */
    public static function getOptions()
    {
        return [
            'form_action_path',
            'form_action_route_name',
            'form_action_route_parameters',
            'form_method',
            'form_enctype'
        ];
    }

    /**
     * @param BlockView             $view
     * @param array                 $options
     * @param FormAccessorInterface $formAccessor
     */
    public static function buildView(BlockView $view, array $options, FormAccessorInterface $formAccessor)
    {
        if (isset($options['form_action_path']) || array_key_exists('form_action_path', $options)) {
            $view->vars['action_path'] = $options['form_action_path'];
        } elseif (isset($options['form_action_route_name']) || array_key_exists('form_action_route_name', $options)) {
            $view->vars['action_route_name']       = $options['form_action_route_name'];
            $view->vars['action_route_parameters'] = isset($options['form_action_route_parameters'])
                ? $options['form_action_route_parameters']
                : [];
        } else {
            $view->vars['action_path'] = $formAccessor->getForm()->getConfig()->getAction();
        }
        if (isset($options['form_method']) || array_key_exists('form_method', $options)) {
            $view->vars['method'] = $options['form_method'];
        } else {
            $view->vars['method'] = $formAccessor->getForm()->getConfig()->getMethod();
        }
        if (isset($options['form_enctype']) || array_key_exists('form_enctype', $options)) {
            $view->vars['enctype'] = $options['form_enctype'];
        } elseif ($formAccessor->getView()->vars['multipart']) {
            $view->vars['enctype'] = 'multipart/form-data';
        }
    }

    /**
     * @param BlockView $view
     */
    public static function finishView(BlockView $view)
    {
        // final check of the view vars and their modification (if required)
        // we have to do this in the finishView because only here we can be sure that
        // expressions have been evaluated (if $context.expressions_evaluate is true)
        if (empty($view->vars['action_path'])) {
            unset($view->vars['action_path']);
        }
        if (empty($view->vars['action_route_name'])) {
            unset($view->vars['action_route_name'], $view->vars['action_route_parameters']);
        }
        if (empty($view->vars['method'])) {
            unset($view->vars['method']);
        } else {
            $view->vars['method'] = strtoupper($view->vars['method']);
        }
        if (empty($view->vars['enctype'])) {
            unset($view->vars['enctype']);
        }
    }
}
