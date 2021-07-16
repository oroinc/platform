<?php

namespace Oro\Bundle\ViewSwitcherBundle\Twig;

use Oro\Bundle\UIBundle\Provider\UserAgentProviderInterface;
use Psr\Container\ContainerInterface;
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
    /** @var ContainerInterface */
    private $container;

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
     * Check by user-agent if request was from mobile device,
     * or the request has cookie for forced mobile version
     *
     * @return bool
     */
    public function isMobile()
    {
        $request = $this->container->get('request_stack')
            ->getMasterRequest();

        $isForceMobile = $request->cookies->get('demo_version') === 'mobile';

        return $isForceMobile || $this->container->get('oro_ui.user_agent_provider')->getUserAgent()->isMobile();
    }

    /**
     * Check by user-agent if request was from desktop device
     * and the request does not have cookie for forced mobile version
     *
     * @return bool
     */
    public function isDesktop()
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
            'request_stack' => RequestStack::class,
        ];
    }
}
