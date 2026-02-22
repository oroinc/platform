<?php

namespace Oro\Bundle\AttachmentBundle\Twig;

use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension that provides is_webp_enabled_if_supported() function
 * to check is "if_supported" webp strategy enabled.
 */
class WebpStrategyExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_webp_enabled_if_supported', [$this, 'isEnabledIfSupported'])
        ];
    }

    public function isEnabledIfSupported(): bool
    {
        return $this->getWebpConfiguration()->isEnabledIfSupported();
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            WebpConfiguration::class
        ];
    }

    private function getWebpConfiguration(): WebpConfiguration
    {
        return $this->container->get(WebpConfiguration::class);
    }
}
