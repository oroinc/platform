<?php

namespace Oro\Bundle\AsseticBundle\Twig;

use Symfony\Bundle\AsseticBundle\Factory\AssetFactory;

use Oro\Bundle\AsseticBundle\AssetsConfiguration;

class AsseticExtension extends \Twig_Extension
{
    /**
     * @var AssetsConfiguration
     */
    protected $assetsConfiguration;

    /**
     * @var AssetFactory
     */
    protected $assetFactory;

    /**
     * @param AssetsConfiguration $assetsConfiguration
     * @param AssetFactory $assetFactory
     */
    public function __construct(AssetsConfiguration $assetsConfiguration, AssetFactory $assetFactory)
    {
        $this->assetsConfiguration = $assetsConfiguration;
        $this->assetFactory = $assetFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenParsers()
    {
        return array(
            new AsseticTokenParser($this->assetsConfiguration, $this->assetFactory, 'oro_css', 'css/*.css'),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'oro_assetic';
    }
}
