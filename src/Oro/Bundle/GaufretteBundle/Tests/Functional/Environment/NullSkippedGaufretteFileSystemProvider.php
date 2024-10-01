<?php

namespace Oro\Bundle\GaufretteBundle\Tests\Functional\Environment;

class NullSkippedGaufretteFileSystemProvider implements SkippedGaufretteFileSystemProviderInterface
{
    #[\Override]
    public function isFileSystemSkipped(string $fileSystem): bool
    {
        return false;
    }
}
