<?php

namespace Oro\Bundle\ApiBundle\Config;

use Symfony\Component\HttpKernel\Config\FileLocator;

/**
 * FileLocator for API documentation resources.
 *
 * Extends the HttpKernel FileLocator to support additional search paths for API documentation files.
 * Required for bundle-less ORO platform configurations where documentation files are located
 * in project directories rather than bundle resource paths.
 */
class ApiDocFileLocator extends FileLocator
{
    /**
     * @param array $paths Array of directory paths to search in.
     */
    public function setPaths(array $paths): void
    {
        $this->paths = $paths;
    }
}
