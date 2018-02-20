<?php

namespace Oro\Bundle\ActionBundle\Twig;

use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\OptionsHelper;
use Oro\Bundle\ActionBundle\Provider\ButtonProvider;
use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OperationExtension extends \Twig_Extension
{
    const NAME = 'oro_action';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return RouteProviderInterface
     */
    protected function getRouteProvider()
    {
        return $this->container->get('oro_action.provider.route');
    }

    /**
     * @return ContextHelper
     */
    protected function getContextHelper()
    {
        return $this->container->get('oro_action.helper.context');
    }

    /**
     * @return OptionsHelper
     */
    protected function getOptionsHelper()
    {
        return $this->container->get('oro_action.helper.options');
    }

    /**
     * @return ButtonProvider
     */
    protected function getButtonProvider()
    {
        return $this->container->get('oro_action.provider.button');
    }

    /**
     * @return ButtonSearchContextProvider
     */
    protected function getButtonSearchContextProvider()
    {
        return $this->container->get('oro_action.provider.button_search_context');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oro_action_widget_parameters',
                [$this, 'getActionParameters'],
                ['needs_context' => true]
            ),
            new \Twig_SimpleFunction('oro_action_widget_route', [$this, 'getWidgetRoute']),
            new \Twig_SimpleFunction('oro_action_frontend_options', [$this, 'getFrontendOptions']),
            new \Twig_SimpleFunction('oro_action_has_buttons', [$this, 'hasButtons']),
        ];
    }

    /**
     * @param array $context
     *
     * @return array
     */
    public function getActionParameters(array $context)
    {
        return $this->getContextHelper()->getActionParameters($context);
    }

    /**
     * @return string
     */
    public function getWidgetRoute()
    {
        return $this->getRouteProvider()->getWidgetRoute();
    }

    /**
     * @param ButtonInterface $button
     *
     * @return array
     */
    public function getFrontendOptions(ButtonInterface $button)
    {
        return $this->getOptionsHelper()->getFrontendOptions($button);
    }

    /**
     * @param array $context
     *
     * @return bool
     */
    public function hasButtons(array $context)
    {
        return $this->getButtonProvider()->hasButtons(
            $this->getButtonSearchContextProvider()->getButtonSearchContext(
                $this->getContextHelper()->getContext($context)
            )
        );
    }
}
