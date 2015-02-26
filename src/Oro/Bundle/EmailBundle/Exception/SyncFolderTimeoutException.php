<?php

namespace Oro\Bundle\EmailBundle\Exception;

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
