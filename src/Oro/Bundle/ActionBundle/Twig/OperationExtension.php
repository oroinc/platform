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
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
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

    public function getActionParameters(array $context): array
    {
        return $this->getContextHelper()->getActionParameters($context);
    }

    public function getWidgetRoute(): string
    {
        return $this->getRouteProvider()->getWidgetRoute();
    }

    public function getFrontendOptions(ButtonInterface $button): array
    {
        return $this->getOptionsHelper()->getFrontendOptions($button);
    }

    public function hasButtons(array $context): bool
    {
        return $this->getButtonProvider()->hasButtons(
            $this->getButtonSearchContextProvider()->getButtonSearchContext(
                $this->getContextHelper()->getContext($context)
            )
        );
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_action.provider.route' => RouteProviderInterface::class,
            ContextHelper::class,
            OptionsHelper::class,
            ButtonProvider::class,
            ButtonSearchContextProvider::class
        ];
    }

    private function getRouteProvider(): RouteProviderInterface
    {
        return $this->container->get('oro_action.provider.route');
    }

    private function getContextHelper(): ContextHelper
    {
        return $this->container->get(ContextHelper::class);
    }

    private function getOptionsHelper(): OptionsHelper
    {
        return $this->container->get(OptionsHelper::class);
    }

    private function getButtonProvider(): ButtonProvider
    {
        return $this->container->get(ButtonProvider::class);
    }

    private function getButtonSearchContextProvider(): ButtonSearchContextProvider
    {
        return $this->container->get(ButtonSearchContextProvider::class);
    }
}
