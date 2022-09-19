<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * The factory for configuration resources Finder.
 */
class ConfigFinderFactory
{
    public function __construct(private array $kernelBundles, private KernelInterface $kernel)
    {
    }

    public function create(string $subDir, string $appSubDir, string $filePattern): Finder
    {
        $finder = new Finder();
        $finder->in($this->getConfigDirectories($subDir, $appSubDir))->name($filePattern);

        return $finder;
    }

    private function getConfigDirectories(string $subDirectory, string $appSubDir): array
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
        $appDirectory = $this->kernel->getProjectDir() . $appSubDir;
        if (is_dir($appDirectory)) {
            $configDirectories[] = realpath($appDirectory);
        }

        return $configDirectories;
    }
}
