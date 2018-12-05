<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification;

/**
 * Fiind the relative path for the behat feature
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
        $basePath = rtrim($this->basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($featurePath, 'vendor' . DIRECTORY_SEPARATOR) !== 0) {
            $basePath .= sprintf('..%s..%s', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR);
        }

        $featureDirectories = explode(DIRECTORY_SEPARATOR, realpath($basePath . $featurePath));

        return implode(DIRECTORY_SEPARATOR, array_diff($featureDirectories, $this->basePathDirectories));
    }
}
