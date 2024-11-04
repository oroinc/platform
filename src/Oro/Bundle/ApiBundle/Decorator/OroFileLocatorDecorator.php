<?php

namespace Oro\Bundle\ApiBundle\Decorator;

use Symfony\Component\Config\FileLocatorInterface;

/**
 * A file locator decorator that modifies path resolution
 * by trimming unnecessary slashes from non-existent absolute paths.
 */
class OroFileLocatorDecorator implements FileLocatorInterface
{
    public function __construct(
        private FileLocatorInterface $decorated,
        private readonly ?string $projectDir = null,
    ) {
    }

    public function locate(string $name, ?string $currentPath = null, bool $first = true): string|array
    {
        $name = realpath($name) ? $name : ltrim($name, '/');
        $currentPath = $currentPath ?? $this->projectDir;

        return $this->decorated->locate($name, $currentPath, $first);
    }
}
