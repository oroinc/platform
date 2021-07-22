<?php

namespace Oro\Bundle\ViewSwitcherBundle\Twig;

use Oro\Bundle\UIBundle\Provider\UserAgent;
use Oro\Bundle\UIBundle\Provider\UserAgentProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions for mobile device detection:
 *   - isMobileVersion
 *   - isDesktopVersion
 */
class DemoMobileExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('isMobileVersion', [$this, 'isMobile']),
            new TwigFunction('isDesktopVersion', [$this, 'isDesktop'])
        ];
    }

    /**
     * Checks by user-agent if request was from mobile device,
     * or the request has cookie for forced mobile version.
     */
    public function isMobile(): bool
    {
        $masterRequest = $this->getMasterRequest();
        $isForceMobile = null !== $masterRequest && $masterRequest->cookies->get('demo_version') === 'mobile';

        return $isForceMobile || $this->getUserAgent()->isMobile();
    }

    /**
     * Checks by user-agent if request was from desktop device
     * and the request does not have cookie for forced mobile version.
     */
    public function isDesktop(): bool
    {
        return !$this->isMobile();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_ui.user_agent_provider' => UserAgentProviderInterface::class,
            RequestStack::class,
        ];
    }

    private function getUserAgent(): UserAgent
    {
        /** @var UserAgentProviderInterface $userAgentProvider */
        $userAgentProvider = $this->container->get('oro_ui.user_agent_provider');

        return $userAgentProvider->getUserAgent();
    }

    private function getMasterRequest(): ?Request
    {
        return $this->container->get(RequestStack::class)->getMasterRequest();
    }
}
