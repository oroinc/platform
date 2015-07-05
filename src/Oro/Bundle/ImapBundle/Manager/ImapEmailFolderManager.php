<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Zend\Mail\Storage\Exception as MailException;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Connector\ImapConnector;
use Oro\Bundle\ImapBundle\Mail\Storage\Folder;

/**
 * Class ImapEmailFolderManager
 *
 * @package Oro\Bundle\ImapBundle\Manager
 *
 */
class ImapEmailFolderManager
{
    /**
     * @var ImapConnector
     */
    protected $connector;

    /**
     * @var EmailOrigin
     */
    protected $origin;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @param ImapConnector $connector
     * @param EntityManager $em
     * @param EmailOrigin $origin
     */
    public function __construct(ImapConnector $connector, EntityManager $em, EmailOrigin $origin)
    {
        $this->connector = $connector;
        $this->em = $em;
        $this->origin = $origin;
    }

    /**
     * @return EmailFolder[]
     */
    public function getFolders()
    {
        $folders = $this->connector->findFolders();

        $emailFolders = $this->processFolders($folders);

        // todo wtf is going on here?
/*        if ($this->origin->getId()) {
            $existingFolders = $this->origin->getRootFolders();

            $emailFolders = $this->mergeFolders($emailFolders, $existingFolders);
        }*/

        return $emailFolders;
    }

    /**
     * @param Folder[] $srcFolders
     *
     * @return EmailFolder[]
     */
    protected function processFolders(array $srcFolders)
    {
        $folders = [];
        foreach ($srcFolders as $srcFolder) {
            $folder = null;
            $uidValidity = $this->getUidValidity($srcFolder);

            if ($uidValidity !== null) {
                $folder = $this->createEmailFolder($srcFolder);
                $folders[] = $folder;
            }

            $childSrcFolders = [];
            foreach ($srcFolder as $childSrcFolder) {
                $childSrcFolders[] = $childSrcFolder;
            }

            $childFolders = $this->processFolders($childSrcFolders);
            if (isset($folder)) {
                foreach ($childFolders as $childFolder) {
                    $folder->addSubFolder($childFolder);
                }
            } else {
                $folders = array_merge($folders, $childFolders);
            }
        }

        return $folders;
    }

    /**
     * @param EmailFolder[] $syncedFolders
     * @param EmailFolder[]|ArrayCollection $existingFolders
     *
     * @return EmailFolder[]
     */
    protected function mergeFolders($syncedFolders, $existingFolders)
    {
        foreach ($syncedFolders as $syncedFolder) {
            $f = $existingFolders->filter(function (EmailFolder $emailFolder) use ($syncedFolder) {
                return $emailFolder->getFullName() === $syncedFolder->getFullName();
            });
            if ($f->isEmpty()) {
                $this->em->persist($syncedFolder);
            } else {
                /** @var EmailFolder $existingFolder */
                $existingFolder = $f->first();

                $syncedSubFolders = $this->mergeFolders(
                    $syncedFolder->getSubFolders(),
                    $existingFolder->getSubFolders()
                );
                $syncedFolder->setSubFolders($syncedSubFolders);
                $existingFolders->remove($existingFolder);
            }
        }

        foreach ($existingFolders as $existingFolder) {
            // todo set outdated instead of removing
            $this->em->remove($existingFolder);
        }

        return $syncedFolders;
    }

    /**
     * @param Folder $srcFolder
     *
     * @return EmailFolder
     */
    protected function createEmailFolder(Folder $srcFolder)
    {
        $folder = new EmailFolder();
        $folder
            ->setFullName($srcFolder->getGlobalName())
            ->setName($srcFolder->getLocalName())
            ->setType($srcFolder->guessFolderType());

        return $folder;
    }

    /**
     * Gets UIDVALIDITY of the given folder
     *
     * @param Folder $folder
     *
     * @return int|null
     */
    protected function getUidValidity(Folder $folder)
    {
        try {
            $this->connector->selectFolder($folder->getGlobalName());

            return $this->connector->getUidValidity();
        } catch (\Exception $e) {
            return null;
        }
    }
}
