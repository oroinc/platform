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
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'is_webp_enabled_if_supported',
                [$this, 'isEnabledIfSupported']
            ),
        ];
    }

    public function isEnabledIfSupported(): bool
    {
        return $this->container->get(WebpConfiguration::class)->isEnabledIfSupported();
    }

    public static function getSubscribedServices(): array
    {
        return [
            WebpConfiguration::class,
        ];
    }
}
