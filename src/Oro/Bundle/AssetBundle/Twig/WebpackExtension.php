<?php

namespace Oro\Bundle\AssetBundle\Twig;

use Oro\Bundle\AssetBundle\Webpack\WebpackServer;
use Twig\Extension\AbstractExtension;

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
            new \Twig_SimpleFunction('webpack_hmr_enabled', [WebpackServer::class, 'isRunning']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('webpack_asset', [WebpackServer::class, 'getServerUrl']),
        ];
    }
}
