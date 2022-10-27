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
    private ContainerInterface $container;

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
            new TwigFunction('oro_version', [$this, 'getVersion'])
        ];
    }

    public function getVersion(): string
    {
        return $this->getVersionHelper()->getVersion();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_platform.composer.version_helper' => VersionHelper::class,
        ];
    }

    private function getVersionHelper(): VersionHelper
    {
        return $this->container->get('oro_platform.composer.version_helper');
    }
}
