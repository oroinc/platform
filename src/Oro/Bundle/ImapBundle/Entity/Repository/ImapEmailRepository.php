<?php

namespace Oro\Bundle\ImapBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;

class ImapEmailRepository extends EntityRepository
{
    /**
     * @param EmailFolder $folder
     * @param int[]       $uids
     *
     * @return QueryBuilder
     */
    public function getEmailsByUidsQueryBuilder(EmailFolder $folder, array $uids)
    {
        return $this->createQueryBuilder('imap_email')
            ->innerJoin('imap_email.imapFolder', 'imapFolder')
            ->innerJoin('imapFolder.folder', 'folder')
            ->where('folder = :folder AND imap_email.uid IN (:uids)')
            ->setParameter('folder', $folder)
            ->setParameter('uids', $uids);
    }

    /**
     * Get last email sequence uid by folder
     *
     * @param ImapEmailFolder $imapFolder
     *
     * @return int
     */
    public function findLastUidByFolder(ImapEmailFolder $imapFolder)
    {
        try {
            $lastUid = $this->createQueryBuilder('ie')
                ->select('ie.uid')
                ->innerJoin('ie.imapFolder', 'if')
                ->where('if = :imapFolder')
                ->setParameter('imapFolder', $imapFolder)
                ->orderBy('ie.uid', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult();

            return $lastUid;
        } catch (NoResultException $e) {
            return 0;
        }
    }

    /**
     * @param EmailFolder $folder
     * @param int[]       $uids
     *
     * @return int[] Existing UIDs
     */
    public function getExistingUids(EmailFolder $folder, array $uids)
    {
        $rows = $this->getEmailsByUidsQueryBuilder($folder, $uids)
            ->select('imap_email.uid')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[] = $row['uid'];
        }

        return $result;
    }

    /**
     * @param EmailOrigin $origin
     * @param string[]    $messageIds
     *
     * @return QueryBuilder
     */
    public function getEmailsByMessageIdsQueryBuilder(EmailOrigin $origin, array $messageIds)
    {
        return $this->createQueryBuilder('imap_email')
            ->innerJoin('imap_email.imapFolder', 'imap_folder')
            ->innerJoin('imap_email.email', 'email')
            ->innerJoin('email.emailUsers', 'email_users')
            ->innerJoin('email_users.folders', 'folders')
            ->where('folders.origin = :origin AND email.messageId IN (:messageIds)')
            ->setParameter('origin', $origin)
            ->setParameter('messageIds', $messageIds);
    }

    /**
     * @param EmailOrigin $origin
     * @param string[]    $messageIds
     *
     * @return ImapEmail[] Existing emails
     */
    public function getEmailsByMessageIds(EmailOrigin $origin, array $messageIds)
    {
        $rows = $this->getEmailsByMessageIdsQueryBuilder($origin, $messageIds)
            ->select('imap_email, email, email_users, imap_folder, folders')
            ->getQuery()
            ->getResult();

        return $rows;
    }

    /**
     * @param integer $folder - id of Folder
     * @param integer $email  - id of Email
     *
     * @return integer|false
     */
    public function getUid($folder, $email)
    {
        $query = $this->createQueryBuilder('e')
            ->select('e.uid')
            ->innerJoin('e.imapFolder', 'if')
            ->where('e.email = :email AND if.folder = :folder')
            ->setParameter('email', $email)
            ->setParameter('folder', $folder)
            ->getQuery();

        return $query->getSingleScalarResult();
    }

    /**
     * @param             $uids
     * @param EmailFolder $folder
     *
     * @return array
     */
    public function getEmailUserIdsByUIDs($uids, EmailFolder $folder)
    {
        $qb = $this->createQueryBuilder('ie');

        $emailUserIds = $qb->select('email_user.id')
            ->leftJoin('ie.email', 'email')
            ->leftJoin('email.emailUsers', 'email_user')
            ->leftJoin('email_user.folders', 'folders')
            ->andWhere($qb->expr()->in('folders', ':folder'))
            ->andWhere($qb->expr()->in('ie.uid', ':uids'))
            ->setParameter('uids', $uids)
            ->setParameter('folder', $folder)
            ->getQuery()->getArrayResult();

        $ids = [];
        foreach ($emailUserIds as $emailUserId) {
            $ids[] = $emailUserId['id'];
        }

        return $ids;
    }
}
