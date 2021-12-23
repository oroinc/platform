<?php

namespace Oro\Bundle\ImapBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

/**
 * This entity listener handles prePersist doctrine entity event
 * and creates ImapEmailFolder entities based on information from UserEmailOrigin
 */
class UserEmailOriginListener
{
    /** @var ManagerRegistry */
    protected $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Create ImapEmailFolder instances for each newly created EmailFolder related to UserEmailOrigin
     */
    public function prePersist(UserEmailOrigin $origin)
    {
        if (!$origin->getFolders()->isEmpty()) {
            $folders = $origin->getRootFolders();

            $this->createImapEmailFolders($folders);
        }
    }

    protected function createImapEmailFolders($folders): void
    {
        foreach ($folders as $folder) {
            if ($folder->getId() === null) {
                $imapEmailFolder = new ImapEmailFolder();
                $imapEmailFolder->setUidValidity(0);
                $imapEmailFolder->setFolder($folder);

                $this->doctrine->getManager()->persist($imapEmailFolder);

                if ($folder->hasSubFolders()) {
                    $this->createImapEmailFolders($folder->getSubFolders());
                }
            }
        }
    }
}
