<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification;

/**
 * Find the relative path for the behat feature
 */
class FeaturePathLocator
{
    private array $basePathDirectories;

    /**
     * @param string $basePath
     */
    public function __construct($basePath)
    {
        $this->basePathDirectories = explode(DIRECTORY_SEPARATOR, realpath($basePath));
    }

    /**
     * @param string $featurePath
     * @return string
     */
    public function getRelativePath($featurePath)
    {
        $featurePathParts = explode(DIRECTORY_SEPARATOR, realpath($featurePath));
        foreach ($this->basePathDirectories as $part) {
            if (count($featurePathParts) > 0 && $part === $featurePathParts[0]) {
                array_shift($featurePathParts);
            } else {
                break;
            }
        }

        return implode(DIRECTORY_SEPARATOR, $featurePathParts);
    }
}
