<?php

namespace Oro\Bundle\ActionBundle\Twig;

use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\OptionsHelper;
use Oro\Bundle\ActionBundle\Provider\ButtonProvider;
use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to render operation (action) buttons:
 *   - oro_action_widget_parameters
 *   - oro_action_widget_route
 *   - oro_action_frontend_options
 *   - oro_action_has_buttons
 */
class OperationExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

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
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'oro_action_widget_parameters',
                [$this, 'getActionParameters'],
                ['needs_context' => true]
            ),
            new TwigFunction('oro_action_widget_route', [$this, 'getWidgetRoute']),
            new TwigFunction('oro_action_frontend_options', [$this, 'getFrontendOptions']),
            new TwigFunction('oro_action_has_buttons', [$this, 'hasButtons']),
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_action.provider.route' => RouteProviderInterface::class,
            'oro_action.helper.context' => ContextHelper::class,
            'oro_action.helper.options' => OptionsHelper::class,
            'oro_action.provider.button' => ButtonProvider::class,
            'oro_action.provider.button_search_context' => ButtonSearchContextProvider::class,
        ];
    }
}
