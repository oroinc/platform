<?php

namespace Oro\Bundle\ImapBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;

class ImapEmailFolderRepository extends EntityRepository
{
    /**
     * @param EmailOrigin $origin
     * @param bool        $withOutdated
     *
     * @return QueryBuilder
     */
    public function getFoldersByOriginQueryBuilder(EmailOrigin $origin, $withOutdated = false)
    {
        $qb = $this->createQueryBuilder('imap_folder')
            ->innerJoin('imap_folder.folder', 'folder')
            ->where('folder.origin = :origin')
            ->setParameter('origin', $origin);
        if (!$withOutdated) {
            $qb->andWhere('folder.outdatedAt IS NULL');
        }

        return $qb;
    }

    /**
     * @param EmailOrigin $origin
     * @param bool        $withOutdated
     * @param bool        $syncEnabled - values can be 1|0|-1
     *
     * @return ImapEmailFolder[]
     */
    public function getFoldersByOrigin(
        EmailOrigin $origin,
        $withOutdated = false,
        $syncEnabled = EmailFolder::SYNC_ENABLED_IGNORE
    ) {
        $qb = $this->getFoldersByOriginQueryBuilder($origin, $withOutdated)
            ->select('imap_folder, folder');

        if ($syncEnabled !== EmailFolder::SYNC_ENABLED_IGNORE) {
            $qb->andWhere('folder.syncEnabled = :syncEnabled')
            ->setParameter('syncEnabled', $syncEnabled);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param EmailOrigin $origin
     *
     * @return QueryBuilder
     */
    public function getEmptyOutdatedFoldersByOriginQueryBuilder(EmailOrigin $origin)
    {
        return $this->createQueryBuilder('imap_folder')
            ->innerJoin('imap_folder.folder', 'folder')
            ->leftJoin('folder.emailUsers', 'emailUsers')
            ->where('folder.outdatedAt IS NOT NULL AND emailUsers.id IS NULL')
            ->andWhere('folder.origin = :origin')
            ->setParameter('origin', $origin);
    }

    /**
     * @param EmailOrigin $origin
     *
     * @return ImapEmailFolder[]
     */
    public function getEmptyOutdatedFoldersByOrigin(EmailOrigin $origin)
    {
        return $this->getEmptyOutdatedFoldersByOriginQueryBuilder($origin)
            ->select('imap_folder, folder')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param ImapEmailFolder $imapFolder
     */
    public function removeFolder(ImapEmailFolder $imapFolder)
    {
        $this->clearFolder($imapFolder);

        $em = $this->getEntityManager();

        $folder = $imapFolder->getFolder();
        $em->remove($imapFolder);
        $em->remove($folder);

        $em->flush();
    }

    /**
     * @param ImapEmailFolder $imapFolder
     */
    public function clearFolder(ImapEmailFolder $imapFolder)
    {
        $em = $this->getEntityManager();

        $folder = $imapFolder->getFolder();
        $emailUsers = $em->getRepository('OroEmailBundle:EmailUser')->findBy(['folder' => $folder]);

        foreach ($emailUsers as $emailUser) {
            $em->remove($emailUser);

            $email = $emailUser->getEmail();
            $imapEmail = $em->getRepository('OroImapBundle:ImapEmail')->findOneBy([
                'email' => $email,
                'imapFolder' => $imapFolder,
            ]);
            $em->remove($imapEmail);
        }

        foreach ($emailUsers as $emailUser) {
            $email = $emailUser->getEmail();
            if ($email->getEmailUsers()->isEmpty()) {
                $emailRecipients = $email->getRecipients();
                foreach ($emailRecipients as $emailRecipient) {
                    $em->remove($emailRecipient);
                }

                $em->remove($email);
            }
        }

        $em->flush();
    }
}
