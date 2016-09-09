<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class FormStartType extends AbstractFormType
{
    const NAME = 'form_start';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefined(
            [
                'form_action',
                'form_route_name',
                'form_route_parameters',
                'form_method',
                'form_enctype'
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $formAccessor = $this->getFormAccessor($block->getContext(), $options);

        // form action
        if (isset($options['form_action'])) {
            $path = $options['form_action'];
            if ($path) {
                $view->vars['action_path'] = $path;
            }
        } elseif (isset($options['form_route_name'])) {
            $routeName = $options['form_route_name'];
            if ($routeName) {
                $view->vars['action_route_name']       = $routeName;
                $view->vars['action_route_parameters'] = isset($options['form_route_parameters'])
                    ? $options['form_route_parameters']
                    : [];
            }
        } else {
            $action = $formAccessor->getAction();
            $path   = $action->getPath();
            if ($path) {
                $view->vars['action_path'] = $path;
            } else {
                $routeName = $action->getRouteName();
                if ($routeName) {
                    $view->vars['action_route_name']       = $routeName;
                    $view->vars['action_route_parameters'] = $action->getRouteParameters();
                }
            }
        }

        // form method
        $method = isset($options['form_method'])
            ? strtoupper($options['form_method'])
            : $formAccessor->getMethod();
        if ($method) {
            $view->vars['method'] = $method;
        }

        // form enctype
        $enctype = isset($options['form_enctype'])
            ? $options['form_enctype']
            : $formAccessor->getEnctype();
        if ($enctype) {
            $view->vars['enctype'] = $enctype;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        $formAccessor = $this->getFormAccessor($block->getContext(), $options);

        $view->vars['form'] = $formAccessor->getView();

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
        }
        if (empty($view->vars['enctype'])) {
            unset($view->vars['enctype']);
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
