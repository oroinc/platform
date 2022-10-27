<?php

namespace Oro\Bundle\ViewSwitcherBundle\Twig;

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
    private ?bool $isMobile = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
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
        if (null === $this->isMobile) {
            $masterRequest = $this->getMasterRequest();
            $isForceMobile = null !== $masterRequest && $masterRequest->cookies->get('demo_version') === 'mobile';
            $this->isMobile = $isForceMobile || $this->getUserAgentProvider()->getUserAgent()->isMobile();
        }

        return $this->isMobile;
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

    private function getUserAgentProvider(): UserAgentProviderInterface
    {
        return $this->container->get('oro_ui.user_agent_provider');
    }

    private function getMasterRequest(): ?Request
    {
        return $this->container->get(RequestStack::class)->getMainRequest();
    }
}
