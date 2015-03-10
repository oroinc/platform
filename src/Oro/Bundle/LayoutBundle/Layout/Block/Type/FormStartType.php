<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class FormStartType extends AbstractFormType
{
    const NAME = 'form_start';

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $formAccessor = $this->getFormAccessor($block->getContext(), $options);

        $view->vars['form'] = $formAccessor->getView();
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
        $method = $formAccessor->getMethod();
        if ($method) {
            $view->vars['method'] = $method;
        }
        $enctype = $formAccessor->getEnctype();
        if ($enctype) {
            $view->vars['enctype'] = $enctype;
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
