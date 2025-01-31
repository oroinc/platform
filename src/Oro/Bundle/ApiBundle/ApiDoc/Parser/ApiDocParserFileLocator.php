<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Parser;

use Symfony\Component\Config\FileLocatorInterface;

/**
 * The file locator that is used to load documentation of API resources.
 * It provides a possibility to load files when they are located outside of any bundle.
 */
class ApiDocParserFileLocator implements FileLocatorInterface
{
    public function __construct(
        private readonly FileLocatorInterface $fileLocator,
        private readonly ?string $projectDir = null
    ) {
    }

    #[\Override]
    public function locate(string $name, ?string $currentPath = null, bool $first = true): string|array
    {
        return $this->fileLocator->locate(
            realpath($name) ? $name : ltrim($name, '/'),
            $currentPath ?? $this->projectDir,
            $first
        );
    }
}
