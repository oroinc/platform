<?php

namespace Oro\Bundle\GaufretteBundle\Tests\Functional\Environment;

class NullSkippedGaufretteFileSystemProvider implements SkippedGaufretteFileSystemProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function isFileSystemSkipped(string $fileSystem): bool
    {
        return false;
    }
}
