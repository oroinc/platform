<?php

namespace Oro\Bundle\AssetBundle\Twig;

use Oro\Bundle\AssetBundle\Webpack\WebpackServer;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Provides a Twig function for Webpack HMR:
 *   - webpack_hmr_enabled
 *
 * Provides a Twig filter for Webpack HMR:
 *   - webpack_asset
 */
class WebpackExtension extends AbstractExtension implements ServiceSubscriberInterface
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
            new TwigFunction('webpack_hmr_enabled', [$this, 'isRunning']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('webpack_asset', [$this, 'getServerUrl']),
        ];
    }

    public function isRunning(): bool
    {
        return $this->getWebpackServer()->isRunning();
    }

    public function getServerUrl(string $url = ''): string
    {
        return $this->getWebpackServer()->getServerUrl($url);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_asset.webpack_server' => WebpackServer::class,
        ];
    }

    private function getWebpackServer(): WebpackServer
    {
        return $this->container->get('oro_asset.webpack_server');
    }
}
