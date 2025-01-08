<?php

namespace Oro\Bundle\NavigationBundle\Twig;

use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to support hash navigation:
 *   - oro_is_hash_navigation
 *   - oro_hash_navigation_header
 */
class HashNavExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [RequestStack::class];
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'oro_is_hash_navigation',
                [$this, 'checkIsHashNavigation']
            ),
            new TwigFunction(
                'oro_hash_navigation_header',
                [$this, 'getHashNavigationHeaderConst']
            ),
        ];
    }

    public function checkIsHashNavigation(): bool
    {
        $mainRequest = $this->getMainRequest();

        return
            null !== $mainRequest
            && (
                $mainRequest->headers->get(ResponseHashnavListener::HASH_NAVIGATION_HEADER)
                || $mainRequest->get(ResponseHashnavListener::HASH_NAVIGATION_HEADER)
            );
    }

    public function getHashNavigationHeaderConst(): string
    {
        return ResponseHashnavListener::HASH_NAVIGATION_HEADER;
    }

    private function getMainRequest(): ?Request
    {
        return $this->container->get(RequestStack::class)->getMainRequest();
    }
}
