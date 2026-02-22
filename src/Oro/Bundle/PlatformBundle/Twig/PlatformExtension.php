<?php

namespace Oro\Bundle\PlatformBundle\Twig;

use Oro\Bundle\PlatformBundle\Composer\VersionHelper;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to retrieve the application version:
 *   - oro_version
 */
class PlatformExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_version', [$this, 'getVersion'])
        ];
    }

    public function getVersion(): string
    {
        return $this->getVersionHelper()->getVersion();
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            VersionHelper::class
        ];
    }

    private function getVersionHelper(): VersionHelper
    {
        return $this->container->get(VersionHelper::class);
    }
}
