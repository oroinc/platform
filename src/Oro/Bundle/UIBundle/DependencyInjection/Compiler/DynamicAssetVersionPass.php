<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Oro\Bundle\UIBundle\Asset\RuntimeAssetVersionStrategy;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This compiler pass can be used to set the runtime version strategy for an asset package.
 */
class DynamicAssetVersionPass implements CompilerPassInterface
{
    const ASSET_VERSION_SERVICE_TEMPLATE = 'assets._package_%s';

    /** @var string */
    protected $packageName;

    /**
     * @param string $packageName The name of the asset package for which the dynamic version strategy should be set
     */
    public function __construct($packageName)
    {
        $this->packageName = $packageName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $assetVersionServiceId = sprintf(self::ASSET_VERSION_SERVICE_TEMPLATE, $this->packageName);
        if (!$container->hasDefinition($assetVersionServiceId)) {
            return;
        }
        $package = $container->getDefinition($assetVersionServiceId);

        $version = new ChildDefinition(RuntimeAssetVersionStrategy::class);
        $version->setArgument('$packageName', $this->packageName);

        $package->replaceArgument(1, $version);
    }
}
