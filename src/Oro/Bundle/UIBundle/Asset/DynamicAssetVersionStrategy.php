<?php

namespace Oro\Bundle\UIBundle\Asset;

use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

/**
 * Allows to change the version of an asset package at runtime.
 */
class DynamicAssetVersionStrategy implements VersionStrategyInterface
{
    /** @var string */
    protected $staticVersion;

    /** @var string */
    protected $format;

    /** @var DynamicAssetVersionManager */
    protected $assetVersionManager;

    /** @var string */
    protected $packageName;

    /**
     * @param string      $staticVersion
     * @param string|null $format
     */
    public function __construct($staticVersion, $format = null)
    {
        $this->staticVersion = $staticVersion;
        $this->format        = $format ?: '%s?%s';
    }

    /**
     * @param DynamicAssetVersionManager $assetVersionManager
     */
    public function setAssetVersionManager(DynamicAssetVersionManager $assetVersionManager)
    {
        $this->assetVersionManager = $assetVersionManager;
    }

    /**
     * @param string $packageName
     */
    public function setAssetPackageName($packageName)
    {
        $this->packageName = $packageName;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion($path)
    {
        $dynamicVersion = $this->assetVersionManager->getAssetVersion($this->packageName);

        return !empty($dynamicVersion)
            ? $this->staticVersion . '-' . $dynamicVersion
            : $this->staticVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function applyVersion($path)
    {
        $versionized = sprintf($this->format, ltrim($path, '/'), $this->getVersion($path));

        return $path && '/' === $path[0]
            ? '/' . $versionized
            : $versionized;
    }
}
