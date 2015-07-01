<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Provider\EmailFolderLoaderSelector;
use Oro\Bundle\ImapBundle\Mail\Storage\Folder;

/**
 * This class responsible for binging EmailAddress to owner entities
 */
class EmailFolderManager
{
    /** @var EmailFolderLoaderSelector */
    protected $selector;

    /**
     * Constructor.
     *
     * @param EmailFolderLoaderSelector $selector
     */
    public function __construct(EmailFolderLoaderSelector $selector)
    {
        $this->selector = $selector;
    }

    /**
     * Get  all email folders
     *
     * @param EmailOrigin $email
     *
     * @return bool|Folder[]
     */
    public function getEmailFolders(EmailOrigin $email)
    {
        $loader = $this->selector->select($email);
        $folders = $loader->loadEmailFolders($email);

        return $folders;
    }
}
