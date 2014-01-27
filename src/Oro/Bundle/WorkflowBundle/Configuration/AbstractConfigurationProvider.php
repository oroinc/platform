<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractConfigurationProvider
{
    /**
     * @var string
     */
    protected $configDirectory = '/Resources/config/';

    /**
     * @var array
     */
    protected $kernelBundles = array();

    /**
     * @param array $kernelBundles
     */
    public function __construct(array $kernelBundles)
    {
        $this->kernelBundles = $kernelBundles;
    }

    /**
     * @param array $directoriesWhiteList
     * @return Finder
     */
    protected function getConfigFinder(array $directoriesWhiteList = array())
    {
        $configDirectories = $this->getConfigDirectories($directoriesWhiteList);

        // prepare finder
        $finder = new Finder();
        $finder->in($configDirectories)->name($this->getConfigFilePattern());

        if ($directoriesWhiteList) {
            $finder->filter(
                function ($file) use ($directoriesWhiteList) {
                    foreach ($directoriesWhiteList as $allowedDirectory) {
                        if ($allowedDirectory &&
                            strpos($file, realpath($allowedDirectory) . DIRECTORY_SEPARATOR) === 0
                        ) {
                            return true;
                        }
                    }
                    return false;
                }
            );
        }

        return $finder;
    }

    /**
     * @return array
     */
    protected function getConfigDirectories()
    {
        $configDirectory = str_replace('/', DIRECTORY_SEPARATOR, $this->configDirectory);
        $configDirectories = array();

        foreach ($this->kernelBundles as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            $bundleConfigDirectory = dirname($reflection->getFilename()) . $configDirectory;
            if (is_dir($bundleConfigDirectory) && is_readable($bundleConfigDirectory)) {
                $configDirectories[] = realpath($bundleConfigDirectory);
            }
        }

        return $configDirectories;
    }

    /**
     * Returns file pattern to find
     *
     * @return string
     */
    abstract protected function getConfigFilePattern();
}
