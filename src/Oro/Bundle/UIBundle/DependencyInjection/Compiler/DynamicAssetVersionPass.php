<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This compiler pass can be used to set the dynamic version strategy for an asset package.
 */
class DynamicAssetVersionPass implements CompilerPassInterface
{
    const ASSET_VERSION_SERVICE_TEMPLATE = 'assets._version_%s';
    const ASSET_VERSION_SERVICE_CLASS    = 'Oro\Bundle\UIBundle\Asset\DynamicAssetVersionStrategy';
    const ASSET_VERSION_MANAGER_SERVICE  = 'oro_ui.dynamic_asset_version_manager';

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
        if (!$container->hasDefinition(self::ASSET_VERSION_MANAGER_SERVICE)) {
            return;
        }

        $assetVersionDef = $container->getDefinition($assetVersionServiceId);
        $assetVersionDef->setClass(self::ASSET_VERSION_SERVICE_CLASS);
        $assetVersionDef->addMethodCall(
            'setAssetVersionManager',
            [new Reference(self::ASSET_VERSION_MANAGER_SERVICE)]
        );
        $assetVersionDef->addMethodCall(
            'setAssetPackageName',
            [$this->packageName]
        );
    }
}
