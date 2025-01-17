<?php

namespace Oro\Bundle\UIBundle\Asset;

use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

/**
 * Allows to change the version of an asset package at runtime.
 */
class RuntimeAssetVersionStrategy implements VersionStrategyInterface
{
    private const FORMAT = '%s?v=%s';

    public function __construct(
        private string $packageName,
        private VersionStrategyInterface $wrappedStrategy,
        private DynamicAssetVersionManager $assetVersionManager
    ) {
    }

    #[\Override]
    public function getVersion($path): string
    {
        $dynamicVersion = $this->assetVersionManager->getAssetVersion($this->packageName);

        $staticVersion = $this->wrappedStrategy->getVersion($path);

        return !empty($dynamicVersion)
            ? $staticVersion.'-'.$dynamicVersion
            : $staticVersion;
    }

    #[\Override]
    public function applyVersion($path): string
    {
        $versionized = sprintf(self::FORMAT, ltrim($path, '/'), $this->getVersion($path));

        return $path && '/' === $path[0]
            ? '/'.$versionized
            : $versionized;
    }
}
