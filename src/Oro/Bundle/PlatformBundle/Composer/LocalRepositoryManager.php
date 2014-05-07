<?php

namespace Oro\Bundle\PlatformBundle\Composer;

use Composer\Json\JsonFile;
use Composer\Repository\InstalledFilesystemRepository;

use Symfony\Component\Filesystem\Filesystem;

class LocalRepositoryManager
{
    /**
     * @var string
     */
    protected $vendorDir;

    /**
     * @var string
     */
    protected $file;

    /**
     * @param Filesystem $fs
     * @param string     $vendorDir
     * @param string     $file
     *
     * @throws \RuntimeException
     */
    public function __construct(Filesystem $fs, $vendorDir, $file)
    {
        $this->vendorDir = $vendorDir;
        $this->file = $file;

        $filePath = $this->vendorDir . $this->file;
        if (!$fs->exists($filePath)) {
            throw new \RuntimeException(
                sprintf('File "%s" does not exists', $filePath)
            );
        }
    }

    /**
     * @return InstalledFilesystemRepository
     */
    public function getLocalRepository()
    {
        return new InstalledFilesystemRepository(
            new JsonFile($this->vendorDir . $this->file)
        );
    }
}
