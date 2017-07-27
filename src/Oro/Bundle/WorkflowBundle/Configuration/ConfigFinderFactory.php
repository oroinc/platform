<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Symfony\Component\Finder\Finder;

class ConfigFinderFactory
{
    /** @var array */
    private $kernelBundles;

    /**
     * @param array $kernelBundles
     */
    public function __construct(array $kernelBundles)
    {
        $this->kernelBundles = $kernelBundles;
    }

    /**
     * @param string $subDir
     * @param string $filePattern
     * @return Finder
     */
    public function create(string $subDir, string $filePattern): Finder
    {
        $finder = new Finder();
        $finder->in($this->getConfigDirectories($subDir))->name($filePattern);

        return $finder;
    }

    /**
     * @param string $subDirectory
     * @return array
     */
    private function getConfigDirectories(string $subDirectory): array
    {
        $configDirectory = str_replace('/', DIRECTORY_SEPARATOR, $subDirectory);
        $configDirectories = [];

        foreach ($this->kernelBundles as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            $bundleConfigDirectory = dirname($reflection->getFileName()) . $configDirectory;
            if (is_dir($bundleConfigDirectory) && is_readable($bundleConfigDirectory)) {
                $configDirectories[] = realpath($bundleConfigDirectory);
            }
        }

        return $configDirectories;
    }
}
