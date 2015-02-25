<?php

namespace Oro\Bundle\EmailBundle\Exception;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;

class SyncFolderTimeoutException extends \RuntimeException
{
    /**
     * @param EmailFolder $folder
     */
    public function __construct(EmailFolder $folder)
    {
        parent::__construct(
            sprintf(
                'Exit because of origin\'s "%d" folder "%s" sync exceeded max save time per batch.',
                $folder->getOrigin()->getId(),
                $folder->getFullName()
            )
        );
    }
}
