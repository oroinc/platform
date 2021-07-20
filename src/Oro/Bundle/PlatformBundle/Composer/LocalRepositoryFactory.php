<?php

namespace Oro\Bundle\PlatformBundle\Composer;

use Composer\Json\JsonFile;
use Composer\Repository\InstalledFilesystemRepository;
use Composer\Repository\WritableRepositoryInterface;

/**
 * The factory to create composer repository by a specific local JSON file.
 */
class LocalRepositoryFactory
{
    /** @var string */
    private $file;

    public function __construct(string $file)
    {
        $this->file = $file;
    }

    /**
     * @return WritableRepositoryInterface
     */
    public function getLocalRepository()
    {
        $repositoryFile = new JsonFile($this->file);
        if (!$repositoryFile->exists()) {
            throw new \RuntimeException(sprintf('File "%s" does not exists.', $this->file));
        }

        return new InstalledFilesystemRepository($repositoryFile);
    }
}
