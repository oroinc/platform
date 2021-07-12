<?php

namespace Oro\Bundle\AssetBundle\Twig;

use Oro\Bundle\AssetBundle\Webpack\WebpackServer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Provide twig helpers for Webpack HMR
 */
class WebpackExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('webpack_hmr_enabled', [WebpackServer::class, 'isRunning']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('webpack_asset', [WebpackServer::class, 'getServerUrl']),
        ];
    }
}
