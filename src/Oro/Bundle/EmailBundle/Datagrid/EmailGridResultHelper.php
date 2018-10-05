<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Helper that modify email grid results
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EmailGridResultHelper
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var EmailOwnerProviderStorage */
    private $emailOwnerProviderStorage;

    /** @var MailboxNameHelper */
    private $mailboxNameHelper;

    /** @var EmailAddressManager */
    private $emailAddressManager;

    /**
     * @param ManagerRegistry           $doctrine
     * @param EmailOwnerProviderStorage $emailOwnerProviderStorage
     * @param MailboxNameHelper         $mailboxNameHelper
     * @param EmailAddressManager       $emailAddressManager
     */
    public function __construct(
        ManagerRegistry $doctrine,
        EmailOwnerProviderStorage $emailOwnerProviderStorage,
        MailboxNameHelper $mailboxNameHelper,
        EmailAddressManager $emailAddressManager
    ) {
        $this->doctrine = $doctrine;
        $this->emailOwnerProviderStorage = $emailOwnerProviderStorage;
        $this->mailboxNameHelper = $mailboxNameHelper;
        $this->emailAddressManager = $emailAddressManager;
    }

    /**
     * @param ResultRecord[] $records
     */
    public function addEmailDirections(array $records)
    {
        $emailUserFolderTypes = $this->loadEmailUserFolderTypes($this->getEmailUserIds($records));
        $fromEmailAddressIds = [];
        foreach ($records as $record) {
            $incoming = false;
            $outgoing = false;
            $emailUserId = $record->getValue('id');
            if (isset($emailUserFolderTypes[$emailUserId])) {
                /** @var string[] $folderTypes */
                $folderTypes = $emailUserFolderTypes[$emailUserId];
                foreach ($folderTypes as $folderType) {
                    if (in_array($folderType, FolderType::incomingTypes(), true)) {
                        $incoming = true;
                    }
                    if (in_array($folderType, FolderType::outgoingTypes(), true)) {
                        $outgoing = true;
                    }
                }
                if (!$incoming && !$outgoing && null !== $record->getValue('ownerId')) {
                    $fromEmailAddressId = $record->getValue('fromEmailAddressId');
                    if (null !== $fromEmailAddressId) {
                        $fromEmailAddressIds[] = $fromEmailAddressId;
                    }
                }
            }
            $record->setValue('incoming', $incoming);
            $record->setValue('outgoing', $outgoing);
        }
        $this->addUnknownEmailDirections($records, array_unique($fromEmailAddressIds));
    }

    /**
     * @param ResultRecord[] $records
     */
    public function addEmailMailboxNames(array $records)
    {
        $mailboxNames = $this->loadEmailMailboxNames($this->getEmailOriginIds($records));
        foreach ($records as $record) {
            $mailboxName = null;
            $originId = $record->getValue('originId');
            if (null !== $originId && isset($mailboxNames[$originId])) {
                $mailboxName = $mailboxNames[$originId];
            }
            $record->setValue('mailboxName', $mailboxName);
        }
    }

    /**
     * @param ResultRecord[] $records
     */
    public function addEmailRecipients(array $records)
    {
        $emailRecipients = $this->loadEmailRecipients($this->getEmailAndThreadIds($records));
        foreach ($records as $record) {
            $recipients = [];
            $emailId = $record->getValue('emailId');
            if (null !== $emailId && isset($emailRecipients[$emailId])) {
                $recipients = $emailRecipients[$emailId];
            }
            $record->setValue('recipients', $recipients);
        }
    }

    /**
     * @param int[] $emailUserIds
     *
     * @return array [email user id => [folder type, ...], ...]
     */
    private function loadEmailUserFolderTypes(array $emailUserIds)
    {
        if (empty($emailUserIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder(EmailUser::class, 'eu');
        $qb
            ->select('eu.id, f.type')
            ->innerJoin('eu.folders', 'f')
            ->where($qb->expr()->in('eu.id', ':emailUserIds'))
            ->setParameter('emailUserIds', $emailUserIds);
        $rows = $qb->getQuery()->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['id']][] = $row['type'];
        }
        foreach ($emailUserIds as $emailUserId) {
            if (!isset($result[$emailUserId])) {
                $result[$emailUserId] = [];
            }
        }

        return $result;
    }

    /**
     * @param int[] $originIds
     *
     * @return array [origin id => mailbox name, ...]
     */
    private function loadEmailMailboxNames(array $originIds)
    {
        if (empty($originIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder(EmailOrigin::class, 'o');
        $qb
            ->select('o, m')
            ->leftJoin('o.mailbox', 'm')
            ->where($qb->expr()->in('o.id', ':originIds'))
            ->setParameter('originIds', $originIds);
        /** @var EmailOrigin[] $origins */
        $origins = $qb->getQuery()->getResult();

        $result = [];
        foreach ($origins as $origin) {
            $result[$origin->getId()] = $this->mailboxNameHelper->getOriginMailboxName($origin);
        }

        return $result;
    }

    /**
     * @param array $emailAndThreadIds [email id => thread id, ...]
     *
     * @return array [email id => [recipient, ...], ...]
     */
    private function loadEmailRecipients(array $emailAndThreadIds)
    {
        if (empty($emailAndThreadIds)) {
            return [];
        }

        $emailIds = [];
        $threadIds = [];
        $threadToEmailMap = [];
        foreach ($emailAndThreadIds as $emailId => $threadId) {
            if (null === $threadId) {
                $emailIds[] = $emailId;
            } else {
                $threadIds[] = $threadId;
                $threadToEmailMap[$threadId] = $emailId;
            }
        }

        $result = $this->loadEmailRecipientsByEmailIds($emailIds);
        $threadRecipients = $this->loadEmailRecipientsByThreadIds($threadIds);
        foreach ($threadRecipients as $threadId => $recipients) {
            $result[$threadToEmailMap[$threadId]] = $recipients;
        }

        return $result;
    }

    /**
     * @param int[] $emailIds
     *
     * @return array [email id => [recipient, ...], ...]
     */
    private function loadEmailRecipientsByEmailIds(array $emailIds)
    {
        if (empty($emailIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder(EmailRecipient::class, 'r');
        $qb
            ->select('r, ea')
            ->innerJoin('r.emailAddress', 'ea')
            ->where($qb->expr()->in('IDENTITY(r.email)', ':emailIds'))
            ->setParameter('emailIds', $emailIds);
        $this->bindEmailAddressOwners($qb, 'ea');
        /** @var EmailRecipient[] $recipients */
        $recipients = $qb->getQuery()->getResult();

        $result = [];
        foreach ($recipients as $recipient) {
            $result[$recipient->getEmail()->getId()][] = $recipient;
        }

        return $result;
    }

    /**
     * @param int[] $threadIds
     *
     * @return array [thread id => [recipient, ...], ...]
     */
    private function loadEmailRecipientsByThreadIds(array $threadIds)
    {
        if (empty($threadIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder(EmailRecipient::class, 'r');
        $qb
            ->select('r, ea, IDENTITY(e.thread) AS threadId')
            ->innerJoin('r.email', 'e')
            ->innerJoin('r.emailAddress', 'ea')
            ->where($qb->expr()->in('IDENTITY(e.thread)', ':threadIds'))
            ->setParameter('threadIds', $threadIds);
        $this->bindEmailAddressOwners($qb, 'ea');
        /** @var array $rows */
        $rows = $qb->getQuery()->getResult();

        $result = [];
        $threadEmailAddresses = [];
        foreach ($rows as $row) {
            /** @var EmailRecipient $recipient */
            $recipient = $row[0];
            $threadId = $row['threadId'];
            $emailAddressId = $recipient->getEmailAddress()->getId();
            if (!isset($threadEmailAddresses[$threadId][$emailAddressId])) {
                $threadEmailAddresses[$threadId][$emailAddressId] = true;
                $result[$threadId][] = $recipient;
            }
        }

        return $result;
    }

    /**
     * @param ResultRecord[] $records
     * @param int[]          $fromEmailAddressIds
     */
    private function addUnknownEmailDirections(array $records, array $fromEmailAddressIds)
    {
        $fromEmailAddressOwners = $this->loadEmailAddressOwners($fromEmailAddressIds);
        foreach ($records as $record) {
            $fromEmailAddressId = $record->getValue('fromEmailAddressId');
            if (isset($fromEmailAddressOwners[$fromEmailAddressId])) {
                if ($record->getValue('ownerId') === $fromEmailAddressOwners[$fromEmailAddressId]) {
                    $record->setValue('outgoing', true);
                } else {
                    $record->setValue('incoming', true);
                }
            } elseif (!$record->getValue('incoming') && !$record->getValue('outgoing')) {
                $record->setValue('incoming', true);
            }
        }
    }

    /**
     * @param int[] $emailAddressIds
     *
     * @return array [email address id => owner id, ...]
     */
    private function loadEmailAddressOwners(array $emailAddressIds)
    {
        if (empty($emailAddressIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder($this->getEmailAddressClass(), 'ea');
        $qb
            ->select('ea')
            ->where($qb->expr()->in('ea.id', ':emailAddressIds'))
            ->setParameter('emailAddressIds', $emailAddressIds);
        $this->bindEmailAddressOwners($qb, 'ea');
        /** @var EmailAddress[] $emailAddresses */
        $emailAddresses = $qb->getQuery()->getResult();

        $result = [];
        foreach ($emailAddresses as $emailAddress) {
            $owner = $emailAddress->getOwner();
            if ($owner instanceof User) {
                $result[$emailAddress->getId()] = $owner->getId();
            }
        }

        return $result;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $emailAddressAlias
     */
    private function bindEmailAddressOwners(QueryBuilder $qb, $emailAddressAlias)
    {
        foreach ($this->emailOwnerProviderStorage->getProviders() as $provider) {
            $ownerFieldName = $this->emailOwnerProviderStorage->getEmailOwnerFieldName($provider);
            $qb
                ->addSelect($ownerFieldName)
                ->leftJoin($emailAddressAlias . '.' . $ownerFieldName, $ownerFieldName);
        }
    }

    /**
     * @param ResultRecord[] $records
     *
     * @return int[]
     */
    private function getEmailUserIds(array $records)
    {
        return $this->getIds($records, 'id');
    }

    /**
     * @param ResultRecord[] $records
     *
     * @return array [email id => thread id, ...]
     */
    private function getEmailAndThreadIds(array $records)
    {
        $result = [];
        foreach ($records as $record) {
            $emailId = $record->getValue('emailId');
            if (null !== $emailId && !array_key_exists($emailId, $result)) {
                $outgoing = $record->getValue('outgoing');
                if (null === $outgoing || $outgoing) {
                    $result[$emailId] = $record->getValue('threadId');
                }
            }
        }

        return $result;
    }

    /**
     * @param ResultRecord[] $records
     *
     * @return int[]
     */
    private function getEmailOriginIds(array $records)
    {
        return $this->getIds($records, 'originId', true);
    }

    /**
     * @param ResultRecord[] $records
     * @param string         $propertyName
     * @param bool           $unique
     * @param callable|null  $filter
     *
     * @return int[]
     */
    private function getIds(array $records, $propertyName, $unique = false, $filter = null)
    {
        $result = [];
        foreach ($records as $record) {
            $id = $record->getValue($propertyName);
            if (null !== $id && (null === $filter || $filter($record))) {
                $result[] = $id;
            }
        }
        if ($unique) {
            $result = array_unique($result);
        }

        return $result;
    }

    /**
     * @param string $entityClass
     *
     * @return EntityManager
     */
    private function getEntityManager($entityClass)
    {
        return $this->doctrine->getManagerForClass($entityClass);
    }

    /**
     * @param string $entityClass
     * @param string $alias
     *
     * @return QueryBuilder
     */
    private function createQueryBuilder($entityClass, $alias)
    {
        return $this->getEntityManager($entityClass)
            ->getRepository($entityClass)
            ->createQueryBuilder($alias);
    }

    /**
     * @return string
     */
    private function getEmailAddressClass()
    {
        return $this->emailAddressManager->getEmailAddressProxyClass();
    }
}
