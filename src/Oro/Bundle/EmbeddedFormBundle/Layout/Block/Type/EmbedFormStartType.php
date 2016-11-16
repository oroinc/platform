<?php

namespace Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Util\BlockUtils;

class EmbedFormStartType extends AbstractFormType
{
    const NAME = 'embed_form_start';

    const SHORT_NAME = 'start';

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
     */
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        BlockUtils::setViewVarsFromOptions(
            $view,
            $options,
            ['form_action', 'form_route_name', 'form_route_parameters', 'form_method', 'form_enctype']
        );
        parent::buildView($view, $block, $options);
    }
    
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function finishView(BlockView $view, BlockInterface $block)
    {
        $formAccessor = $this->getFormAccessor($block->getContext(), $view->vars);

        // form action
        if (!empty($view->vars['form_action'])) {
            $path = $view->vars['form_action'];
            if ($path) {
                $view->vars['action_path'] = $path;
            }
        } elseif (!empty($view->vars['form_route_name'])) {
            $routeName = $view->vars['form_route_name'];
            if ($routeName) {
                $view->vars['action_route_name']       = $routeName;
                $view->vars['action_route_parameters'] = !empty($view->vars['form_route_parameters'])
                    ? $view->vars['form_route_parameters']
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
        $method = $view->vars['form_method']
            ? strtoupper($view->vars['form_method'])
            : $formAccessor->getMethod();
        if ($method) {
            $view->vars['method'] = $method;
        }

        // form enctype
        $enctype = isset($view->vars['form_enctype'])
            ? $view->vars['form_enctype']
            : $formAccessor->getEnctype();
        if ($enctype) {
            $view->vars['enctype'] = $enctype;
        }

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
