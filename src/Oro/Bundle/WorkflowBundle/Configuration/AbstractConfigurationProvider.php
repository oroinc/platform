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
     * @var array
     */
    protected $usedDirectories = null;

    /**
     * @param array $kernelBundles
     */
    public function __construct(array $kernelBundles)
    {
        $this->kernelBundles = $kernelBundles;
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
     * @return Finder
     */
    protected function getConfigFinder()
    {
        $configDirectories = $this->getConfigDirectories();

        // prepare finder
        $finder = new Finder();
        $finder->in($configDirectories)->name($this->getConfigFilePattern());

        return $finder;
    }

    /**
     * @param array $directories
     */
    protected function setUsedDirectories(array $directories = null)
    {
        if ($directories) {
            foreach ($directories as $key => $directory) {
                if (is_dir($directory) && is_readable($directory)) {
                    $directories[$key] = rtrim(realpath($directory), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                } else {
                    unset($directories[$key]);
                }
            }
        }

        $this->usedDirectories = $directories;
    }

    /**
     * @param string $fileName
     * @return bool
     */
    protected function isFileAllowed($fileName)
    {
        if (!is_file($fileName) || !is_readable($fileName)) {
            return false;
        }

        if (null === $this->usedDirectories) {
            return true;
        }

        foreach ($this->usedDirectories as $usedDirectory) {
            if (strpos($fileName, $usedDirectory) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns file pattern to find
     *
     * @return string
     */
    abstract protected function getConfigFilePattern();
}
