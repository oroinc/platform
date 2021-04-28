<?php

namespace Oro\Bundle\GaufretteBundle\Tests\Functional\Environment;

interface SkippedGaufretteFileSystemProviderInterface
{
    public function isFileSystemSkipped(string $fileSystem): bool;
}
