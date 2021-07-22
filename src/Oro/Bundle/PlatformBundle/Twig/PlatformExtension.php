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
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return VersionHelper
     */
    protected function getVersionHelper()
    {
        return $this->container->get('oro_platform.composer.version_helper');
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

    /**
     * @return string
     */
    public function getVersion()
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
}
