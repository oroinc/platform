<?php

namespace Oro\Bundle\GaufretteBundle\Tests\Functional\Environment;

class SkippedGaufretteFileSystemProvider implements SkippedGaufretteFileSystemProviderInterface
{
    /** @var array [filesystem name, ...] */
    private $skippedFilesystems;

    /** @var SkippedGaufretteFileSystemProviderInterface */
    private $innerProvider;

    public function __construct(SkippedGaufretteFileSystemProviderInterface $innerProvider, array $skippedFilesystems)
    {
        $this->innerProvider = $innerProvider;
        $this->skippedFilesystems = $skippedFilesystems;
    }

    /**
     * {@inheritDoc}
     */
    public function isFileSystemSkipped(string $fileSystem): bool
    {
        if (in_array($fileSystem, $this->skippedFilesystems, true)) {
            return true;
        }

        return $this->innerProvider->isFileSystemSkipped($fileSystem);
    }
}
