<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
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
     * Load config file and include imports.
     *
     * @param \SplFileInfo $file
     * @throws InvalidConfigurationException
     * @return array
     */
    protected function loadConfigFile(\SplFileInfo $file)
    {
        $realPathName = $file->getRealPath();
        $configData = Yaml::parse(file_get_contents($realPathName)) ? : [];

        if (array_key_exists('imports', $configData) && is_array($configData['imports'])) {
            $imports = $configData['imports'];
            unset($configData['imports']);
            foreach ($imports as $importData) {
                if (array_key_exists('resource', $importData)) {
                    $resourceFile = new \SplFileInfo($file->getPath() . DIRECTORY_SEPARATOR . $importData['resource']);
                    if ($resourceFile->isReadable()) {
                        $includedData = $this->loadConfigFile($resourceFile);
                        $configData = array_merge_recursive($configData, $includedData);
                    } else {
                        throw new InvalidConfigurationException(
                            sprintf('Resource "%s" is unreadable', $resourceFile->getBasename())
                        );
                    }
                }
            }
        }

        return $configData;
    }

    /**
     * Returns file pattern to find
     *
     * @return string
     */
    abstract protected function getConfigFilePattern();
}
