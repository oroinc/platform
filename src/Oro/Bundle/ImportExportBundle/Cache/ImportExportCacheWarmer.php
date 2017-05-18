<?php

namespace Oro\Bundle\ImportExportBundle\Cache;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;
use Symfony\Component\HttpKernel\Kernel;

use Oro\Bundle\InstallerBundle\CommandExecutor;

/**
 * Actually not a warmer at all. It just copies all import_export
 * files to the new cache directory on cache:clear so that they
 * can not be lost during the process.
 *
 * @deprecated Totally specific for 1.0/2.0. Removed in future versions.
 */
class ImportExportCacheWarmer extends CacheWarmer
{
    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * @param Kernel $kernel
     */
    public function __construct(
        Kernel $kernel
    ) {
        $this->kernel = $kernel;
    }

    /**
     * @param string $cacheDir
     */
    public function warmUp($cacheDir)
    {
        // we're waiting for cache:warmup being run inside cache:clear
        if (!CommandExecutor::isCommandRunning('cache:clear')) {
            return;
        }

        // $cacheDir will contain the temp directory, that holds the files
        // that are needed to be copied to. we need to construct the source
        // cache directory though and we can do it using the parts we have.
        $env = $this->kernel->getEnvironment();

        $destinationCacheDir = $cacheDir . DIRECTORY_SEPARATOR . 'import_export';

        $cacheDir = explode(DIRECTORY_SEPARATOR, $cacheDir);

        // cut out the env directory on the path
        array_pop($cacheDir);

        $sourceCacheDir = implode(DIRECTORY_SEPARATOR, $cacheDir)
                          . DIRECTORY_SEPARATOR
                          . $env
                          . DIRECTORY_SEPARATOR
                          . 'import_export';

        if (!is_dir($sourceCacheDir)) {
            return;
        }

        // if directory does not exist, create it
        @mkdir($destinationCacheDir);

        // this should never happen
        if (!is_dir($destinationCacheDir)) {
            throw new \RuntimeException('Directory could not be created: ' . $destinationCacheDir);
        }

        // copy all files
        foreach (Finder::create()->files()->in($sourceCacheDir) as $file) {
            copy($file->getRealPath(), $destinationCacheDir . DIRECTORY_SEPARATOR . $file->getFilename());
        }
    }

    /**
     * @return bool
     */
    public function isOptional()
    {
        return false;
    }
}
