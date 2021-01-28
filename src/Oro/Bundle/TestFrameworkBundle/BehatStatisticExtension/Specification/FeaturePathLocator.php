<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification;

/**
 * Find the relative path for the behat feature
 */
class FeaturePathLocator
{
    /**
     * @var string
     */
    private $basePath;

    /**
     * @var array
     */
    private $basePathDirectories;

    /**
     * @param string $basePath
     */
    public function __construct($basePath)
    {
        $this->basePath = $basePath;
        $this->basePathDirectories = explode(DIRECTORY_SEPARATOR, realpath($this->basePath));
    }

    /**
     * @param string $featurePath
     * @return string
     */
    public function getRelativePath($featurePath)
    {
        $featureDirectories = explode(DIRECTORY_SEPARATOR, realpath($featurePath));

        return implode(DIRECTORY_SEPARATOR, array_diff($featureDirectories, $this->basePathDirectories));
    }
}
