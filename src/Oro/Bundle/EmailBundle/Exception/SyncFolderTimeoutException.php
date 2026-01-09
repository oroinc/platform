<?php

namespace Oro\Bundle\EmailBundle\Exception;

/**
 * Thrown when email folder synchronization exceeds the maximum time limit.
 *
 * This exception is raised when synchronizing a specific email folder takes longer than allowed,
 * preventing the synchronization process from consuming excessive resources.
 */
class SyncFolderTimeoutException extends \RuntimeException
{
    /**
     * @param int    $originId   Email origin id
     * @param string $folderName Email folder full name
     */
    public function __construct($originId, $folderName)
    {
        parent::__construct(
            sprintf(
                'Exit because of origin\'s "%d" folder "%s" sync exceeded max save time per batch.',
                $originId,
                $folderName
            )
        );
    }
}
