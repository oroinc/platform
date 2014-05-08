<?php

namespace Oro\Bundle\PlatformBundle\Composer;

use Composer\Json\JsonFile;
use Composer\Repository\InstalledFilesystemRepository;
use Composer\Repository\WritableRepositoryInterface;

use Symfony\Component\Filesystem\Filesystem;

class LocalRepositoryFactory
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @param Filesystem $fs
     * @param string     $file
     *
     * @throws \RuntimeException
     */
    public function __construct(Filesystem $fs, $file)
    {
        $this->file = $file;

        if (!$fs->exists($this->file)) {
            throw new \RuntimeException(
                sprintf('File "%s" does not exists', $this->file)
            );
        }
    }

    /**
     * @return WritableRepositoryInterface
     */
    public function getLocalRepository()
    {
        return new InstalledFilesystemRepository(
            new JsonFile($this->file)
        );
    }
}
