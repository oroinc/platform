<?php

namespace Oro\Bundle\AttachmentBundle\Exception;

use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Thrown when a file protocol is not supported.
 */
class ProtocolNotSupportedException extends IOException
{
    /**
     * @param string $path
     */
    public function __construct($path)
    {
        parent::__construct(
            sprintf('The protocol for the file "%s" is not supported.', $path),
            0,
            null,
            $path
        );
    }
}
