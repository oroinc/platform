<?php

namespace Oro\Bundle\DistributionBundle\Provider;

/**
 * Provides the path to the public directory of the application.
 */
class PublicDirectoryProvider
{
    public function __construct(private readonly string $projectDir)
    {
    }

    public function getPublicDirectory(): string
    {
        $defaultPublicDir = 'public';
        $composerFilePath = $this->projectDir . DIRECTORY_SEPARATOR . 'composer.json';

        if (!file_exists($composerFilePath)) {
            return $this->projectDir . DIRECTORY_SEPARATOR . $defaultPublicDir;
        }

        $composerConfig = json_decode(file_get_contents($composerFilePath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->projectDir . DIRECTORY_SEPARATOR . $defaultPublicDir;
        }

        return $this->projectDir . DIRECTORY_SEPARATOR . ($composerConfig['extra']['public-dir'] ?? $defaultPublicDir);
    }
}
