<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Form\Model\EmailFolderModel;
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
        // retrieve folders from imap
        $folders = $this->connector->findFolders();

        // transform folders into tree of models
        $emailFolderModels = $this->processFolders($folders);

        if ($this->origin->getId()) {
            // renew existing folders if origin already exists
            $existingFolders = $this->getExistingFolders();

            // merge synced and existing folders
            $emailFolderModels = $this->mergeFolders($emailFolderModels, $existingFolders);
            // mark old folders as outdated
            $this->markAsOutdated($existingFolders);

            // flush changes
            $this->em->flush();
        }

        return $this->extractEmailFolders($emailFolderModels);
    }

    /**
     * @param ArrayCollection|ImapEmailFolder[] $existingFolders
     */
    protected function markAsOutdated($existingFolders)
    {
        $outdatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        /** @var ImapEmailFolder $existingFolder */
        foreach ($existingFolders as $existingFolder) {
            $emailFolder = $existingFolder->getFolder();
            $emailFolder->setOutdatedAt($outdatedAt);
        }
    }

    /**
     * @param Folder[]|ArrayCollection $srcFolders
     *
     * @return EmailFolderModel[]
     */
    protected function processFolders(array $srcFolders)
    {
        $emailFolderModels = new ArrayCollection();
        foreach ($srcFolders as $srcFolder) {
            $emailFolderModel = null;
            $uidValidity = $this->getUidValidity($srcFolder);

            if ($uidValidity !== null) {
                $emailFolderModel = $this->createEmailFolderModel($srcFolder, $uidValidity);
                $emailFolderModels->add($emailFolderModel);
            }

            $childSrcFolders = [];
            foreach ($srcFolder as $childSrcFolder) {
                $childSrcFolders[] = $childSrcFolder;
            }

            $childFolders = $this->processFolders($childSrcFolders);
            if (isset($emailFolderModel)) {
                foreach ($childFolders as $childFolder) {
                    $emailFolderModel->addSubFolderModel($childFolder);
                }
            } else {
                foreach ($childFolders as $childFolder) {
                    $emailFolderModels->add($childFolder);
                }
            }
        }

        return $emailFolderModels;
    }

    /**
     * @param EmailFolderModel[]|ArrayCollection $syncedFolderModels
     * @param ImapEmailFolder[]|ArrayCollection $existingImapFolders
     *
     * @return EmailFolderModel[]
     */
    protected function mergeFolders($syncedFolderModels, &$existingImapFolders)
    {
        foreach ($syncedFolderModels as $syncedFolderModel) {
            $f = $existingImapFolders->filter(function (ImapEmailFolder $imapEmailFolder) use ($syncedFolderModel) {
                return $imapEmailFolder->getUidValidity() === $syncedFolderModel->getUidValidity();
            });

            if ($f->isEmpty()) {
                // there is a new folder on server, create it
                $imapEmailFolder = $this->createImapEmailFodler($syncedFolderModel);

                // persist ImapEmailFolder and (by cascade) EmailFolder
                $this->em->persist($imapEmailFolder);
            } else {
                /** @var ImapEmailFolder $existingImapFolder */
                $existingImapFolder = $f->first();
                $emailFolder = $existingImapFolder->getFolder();
                $emailFolder->setName($syncedFolderModel->getEmailFolder()->getName());
                $emailFolder->setFullName($syncedFolderModel->getEmailFolder()->getFullName());
                $emailFolder->setType($syncedFolderModel->getEmailFolder()->getType());
                $emailFolder->setSynchronizedAt($syncedFolderModel->getEmailFolder()->getSynchronizedAt());
                $syncedFolderModel->setEmailFolder($emailFolder);

                $existingImapFolders->removeElement($existingImapFolder);
            }

            if ($syncedFolderModel->hasSubFolderModels()) {
                $syncedFolderModel->setSubFolderModels(
                    $this->mergeFolders(
                        $syncedFolderModel->getSubFolderModels(),
                        $existingImapFolders
                    )
                );
            }
        }

        return $syncedFolderModels;
    }

    /**
     * @param Folder $srcFolder
     *
     * @return EmailFolderModel
     */
    protected function createEmailFolderModel(Folder $srcFolder, $uidValidity)
    {
        $folder = new EmailFolder();
        $folder
            ->setFullName($srcFolder->getGlobalName())
            ->setName($srcFolder->getLocalName())
            ->setType($srcFolder->guessFolderType())
            ->setOrigin($this->origin);

        $emailFolderModel = new EmailFolderModel();
        $emailFolderModel->setUidValidity($uidValidity);
        $emailFolderModel->setEmailFolder($folder);

        return $emailFolderModel;
    }

    /**
     * @param EmailFolderModel $emailFolderModel
     *
     * @return ImapEmailFolder
     */
    protected function createImapEmailFodler(EmailFolderModel $emailFolderModel)
    {
        $imapEmailFolder = new ImapEmailFolder();
        $emailFolder = $emailFolderModel->getEmailFolder();
        $imapEmailFolder->setFolder($emailFolder);
        if ($emailFolderModel->hasParentFolderModel()) {
            $emailFolder->setParentFolder($emailFolderModel->getParentFolderModel()->getEmailFolder());
        }

        $imapEmailFolder->setUidValidity($emailFolderModel->getUidValidity());

        return $imapEmailFolder;
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

    /**
     * @param EmailFolderModel[]|ArrayCollection $emailFolderModels
     *
     * @return EmailFolder[]|ArrayCollection
     */
    protected function extractEmailFolders($emailFolderModels)
    {
        $emailFolders = new ArrayCollection();
        /** @var EmailFolderModel $emailFolderModel */
        foreach ($emailFolderModels as $emailFolderModel) {
            $emailFolder = $emailFolderModel->getEmailFolder();
            if ($emailFolderModel->hasSubFolderModels()) {
                $emailFolder->setSubFolders($this->extractEmailFolders($emailFolderModel->getSubFolderModels()));
            }
            $emailFolders->add($emailFolder);
        }

        return $emailFolders;
    }

    /**
     * @return ImapEmailFolder[]|ArrayCollection
     */
    protected function getExistingFolders()
    {
        // todo move to repository
        $qb = $this->em->createQueryBuilder()
            ->select('ief')
            ->from('OroImapBundle:ImapEmailFolder', 'ief')
            ->leftJoin('ief.folder', 'ef')
            ->where('ef.origin = :origin')
            ->setParameter('origin', $this->origin);

        return new ArrayCollection($qb->getQuery()->getResult());
    }
}
