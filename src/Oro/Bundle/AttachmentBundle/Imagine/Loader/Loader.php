<?php

namespace Oro\Bundle\AttachmentBundle\Imagine\Loader;

use Imagine\File\Loader as BaseLoader;

/**
 * Solves the problem of paths for gaufrette.
 * URL is a universal locator to the resource, as not all methods of verification make it possible to distinguish the
 * difference between the url that points to the local file, so adding additional verification that indicate that
 * the file is located locally.
 */
class Loader extends BaseLoader
{
    public function __construct(string $path, string $protocol)
    {
        parent::__construct($path);
        if ($this->isUrl && str_starts_with($this->path, $protocol.'://')) {
            $this->isUrl = false;
            $this->checkLocalFile();
        }
    }
}
