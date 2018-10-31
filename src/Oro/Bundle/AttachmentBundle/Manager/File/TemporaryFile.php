<?php

namespace Oro\Bundle\AttachmentBundle\Manager\File;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Self destroyed temporary file.
 */
class TemporaryFile extends File
{
    public function __destruct()
    {
        try {
            $filesystem = new FileSystem();
            $filesystem->remove($this->getPathname());
        } catch (IOException $exception) {
            // Throwing exceptions from destructor results in fatal error
        }
    }
}
