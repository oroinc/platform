<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * The base class to locate and parse configuration files.
 */
abstract class AbstractConfigurationProvider
{
    protected string $configDirectory = '/Resources/config/';
    protected string $appConfigDirectory = '/config/oro/';

    public function __construct(
        protected array $kernelBundles,
        protected KernelInterface $kernel
    ) {
    }

    /**
     * @param array $directoriesWhiteList
     * @return Finder
     */
    protected function getConfigFinder(array $directoriesWhiteList = array())
    {
        $configDirectories = $this->getConfigDirectories();

        // prepare finder
        $finder = new Finder();
        $finder->in($configDirectories)->name($this->getConfigFilePattern());

        if ($directoriesWhiteList) {
            $finder->filter(
                function ($file) use ($directoriesWhiteList) {
                    foreach ($directoriesWhiteList as $allowedDirectory) {
                        if ($allowedDirectory &&
                            str_starts_with($file, realpath($allowedDirectory) . DIRECTORY_SEPARATOR)
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
        $configDirectories = [];

        foreach ($this->kernelBundles as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            $bundleConfigDirectory = dirname($reflection->getFilename()) . $configDirectory;
            if (is_dir($bundleConfigDirectory) && is_readable($bundleConfigDirectory)) {
                $configDirectories[] = realpath($bundleConfigDirectory);
            }
        }
        $appConfigurationPath = $this->kernel->getProjectDir() . $this->appConfigDirectory;
        if (is_dir($appConfigurationPath)) {
            $configDirectories[] = realpath($appConfigurationPath);
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
        $configData = Yaml::parse(file_get_contents($realPathName)) ?: [];

        if (array_key_exists('imports', $configData) && is_array($configData['imports'])) {
            $imports = $configData['imports'];
            unset($configData['imports']);
            $configData = $this->processImports($file, $imports, $configData);
        }

        return $configData;
    }

    /**
     * @param \SplFileInfo $file
     * @param array $imports
     * @param array $configData
     * @return array
     */
    protected function processImports(\SplFileInfo $file, array $imports, array $configData)
    {
        $configsData = [$configData];
        foreach ($imports as $importData) {
            if (array_key_exists('resource', $importData)) {
                if (str_starts_with($importData['resource'], '@')) {
                    $filePath = $this->kernel->locateResource($importData['resource']);
                } else {
                    $filePath = $file->getPath() . DIRECTORY_SEPARATOR . $importData['resource'];
                }

                $resourceFile = new \SplFileInfo($filePath);
                if ($resourceFile->isReadable()) {
                    $configsData[] = $this->loadConfigFile($resourceFile);
                } else {
                    throw new InvalidConfigurationException(
                        sprintf('Resource "%s" is unreadable', $resourceFile->getBasename())
                    );
                }
            }
        }

        return array_merge_recursive(...$configsData);
    }

    /**
     * Returns file pattern to find
     *
     * @return string
     */
    abstract protected function getConfigFilePattern();
}
