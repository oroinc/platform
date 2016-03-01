<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Exception\SyncFolderTimeoutException;
use Oro\Bundle\EmailBundle\Model\EmailHeader;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractEmailSynchronizationProcessor implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** Determines how many emails can be stored in a database at once */
    const DB_BATCH_SIZE = 100;

    /** Max time in seconds between saved DB batches */
    const DB_BATCH_TIME = 30;

    /** @var EntityManager */
    protected $em;

    /** @var EmailEntityBuilder */
    protected $emailEntityBuilder;

    /** @var int Number of seconds passed to store last emails batch */
    protected $dbBatchSaveTime = -1;

    /** @var int Timestamp when last batch was saved. */
    protected $dbBatchSaveTimestamp = 0;

    /** @var User|Mailbox */
    protected $currentUser;

    /** @var OrganizationInterface */
    protected $currentOrganization;

    /**
     * Constructor
     *
     * @param EntityManager                     $em
     * @param EmailEntityBuilder                $emailEntityBuilder
     * @param KnownEmailAddressCheckerInterface $knownEmailAddressChecker
     */
    protected function __construct(
        EntityManager $em,
        EmailEntityBuilder $emailEntityBuilder,
        KnownEmailAddressCheckerInterface $knownEmailAddressChecker
    ) {
        $this->em                       = $em;
        $this->emailEntityBuilder       = $emailEntityBuilder;
        $this->knownEmailAddressChecker = $knownEmailAddressChecker;
    }

    /**
     * Performs a synchronization of emails for the given email origin.
     *
     * @param EmailOrigin $origin
     * @param \DateTime   $syncStartTime
     */
    abstract public function process(EmailOrigin $origin, $syncStartTime);

    /**
     * @param EmailHeader           $email
     * @param string                $folderType
     * @param User|null             $user
     * @param OrganizationInterface $organization
     *
     * @return bool
     */
    protected function isApplicableEmail(EmailHeader $email, $folderType, $user = null, $organization = null)
    {
        if ($user === null) {
            return $this->isKnownSender($email) && $this->isKnownRecipient($email);
        }
        if ($user instanceof User) {
            if ($organization && !$this->checkOrganization($email, $folderType, $organization)) {
                return false;
            }
            if ($folderType === FolderType::SENT) {
                return $this->isUserSender($user->getId(), $email) && $this->isKnownRecipient($email);
            } else {
                return $this->isKnownSender($email) && $this->isUserRecipient($user->getId(), $email);
            }
        } elseif ($user instanceof Mailbox) {
            if ($folderType === FolderType::SENT) {
                return $this->isMailboxSender($user->getId(), $email);
            } else {
                return $this->isMailboxRecipient($user->getId(), $email);
            }
        }

        return false;
    }

    /**
     * @param EmailHeader $email
     * @param             $folderType
     * @param             $organization
     *
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     * todo CRM-2480 temporary solution for determination of emails` organization
     */
    protected function checkOrganization(EmailHeader $email, $folderType, $organization)
    {
        $helper = new EmailAddressHelper();
        $repo = $this->em->getRepository('OroCRMContactBundle:ContactEmail');
        $qb = $repo->createQueryBuilder('ce');

        if ($folderType === FolderType::SENT) {
            $emailList = array_merge($email->getToRecipients(), $email->getCcRecipients(), $email->getBccRecipients());
        } else {
            $emailList = [$email->getFrom()];
        }
        foreach ($emailList as &$emailAddress) {
            $emailAddress = strtolower($helper->extractPureEmailAddress($emailAddress));
        }
        $query = $qb->where($qb->expr()->in('ce.email', $emailList))->getQuery();
        $result = $query->getResult();

        if ($result) {
            foreach ($result as $contactEmail) {
                if ($contactEmail->getOwner()->getOrganization()->getId() === $organization->getId()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a sender of the given email is registered in the system
     *
     * @param EmailHeader $email
     *
     * @return bool
     */
    protected function isKnownSender(EmailHeader $email)
    {
        return $this->knownEmailAddressChecker->isAtLeastOneKnownEmailAddress(
            $email->getFrom()
        );
    }

    /**
     * Check if at least one recipient of the given email is registered in the system
     *
     * @param EmailHeader $email
     *
     * @return bool
     */
    protected function isKnownRecipient(EmailHeader $email)
    {
        return $this->knownEmailAddressChecker->isAtLeastOneKnownEmailAddress(
            $email->getToRecipients(),
            $email->getCcRecipients(),
            $email->getBccRecipients()
        );
    }

    /**
     * Check if a sender of the given email is the given user
     *
     * @param int         $userId
     * @param EmailHeader $email
     *
     * @return bool
     */
    protected function isUserSender($userId, EmailHeader $email)
    {
        return $this->knownEmailAddressChecker->isAtLeastOneUserEmailAddress(
            $userId,
            $email->getFrom()
        );
    }

    /**
     * Check if the given user is a recipient of the given email
     *
     * @param int         $userId
     * @param EmailHeader $email
     *
     * @return bool
     */
    protected function isUserRecipient($userId, EmailHeader $email)
    {
        return $this->knownEmailAddressChecker->isAtLeastOneUserEmailAddress(
            $userId,
            $email->getToRecipients(),
            $email->getCcRecipients(),
            $email->getBccRecipients()
        );
    }

    /**
     * @param EmailHeader[] $emails
     */
    protected function registerEmailsInKnownEmailAddressChecker(array $emails)
    {
        $addresses = [];
        foreach ($emails as $email) {
            $from = $email->getFrom();
            if (!isset($addresses[$from])) {
                $addresses[$from] = $from;
            }
            $this->addRecipients($addresses, $email->getToRecipients());
            $this->addRecipients($addresses, $email->getCcRecipients());
            $this->addRecipients($addresses, $email->getBccRecipients());
        }
        $this->knownEmailAddressChecker->preLoadEmailAddresses($addresses);
    }

    /**
     * @param string[] $addresses
     * @param string[] $recipients
     */
    protected function addRecipients(&$addresses, $recipients)
    {
        if (!empty($recipients)) {
            foreach ($recipients as $recipient) {
                if (!isset($addresses[$recipient])) {
                    $addresses[$recipient] = $recipient;
                }
            }
        }
    }

    /**
     * Creates email entity and register it in the email entity batch processor
     *
     * @param EmailHeader           $email
     * @param EmailFolder           $folder
     * @param bool                  $isSeen
     * @param User|Mailbox          $owner
     * @param OrganizationInterface $organization
     *
     * @return EmailUser
     */
    protected function addEmailUser(
        EmailHeader $email,
        EmailFolder $folder,
        $isSeen = false,
        $owner = null,
        OrganizationInterface $organization = null
    ) {
        $emailUser = $this->emailEntityBuilder->emailUser(
            $email->getSubject(),
            $email->getFrom(),
            $email->getToRecipients(),
            $email->getSentAt(),
            $email->getReceivedAt(),
            $email->getInternalDate(),
            $email->getImportance(),
            $email->getCcRecipients(),
            $email->getBccRecipients(),
            $owner,
            $organization
        );

        $emailUser
            ->addFolder($folder)
            ->setSeen($isSeen)
            ->setOrigin($folder->getOrigin())
            ->getEmail()
                ->setMessageId($email->getMessageId())
                ->setMultiMessageId($email->getMultiMessageId())
                ->setRefs($email->getRefs())
                ->setXMessageId($email->getXMessageId())
                ->setXThreadId($email->getXThreadId())
                ->setAcceptLanguageHeader($email->getAcceptLanguageHeader());

        return $emailUser;
    }

    /**
     * @param EmailFolder $folder
     * @param array       $messageIds
     * @return EmailUser[]
     */
    protected function getExistingEmailUsers(EmailFolder $folder, array $messageIds)
    {
        $existEmailUsers = [];
        if (empty($messageIds)) {
            return $existEmailUsers;
        }
        $emailUserRepository = $this->em->getRepository('OroEmailBundle:EmailUser');
        $result              = $emailUserRepository->getEmailUsersByFolderAndMessageIds($folder, $messageIds);

        /** @var EmailUser $emailUser */
        foreach ($result as $emailUser) {
            $existEmailUsers[$emailUser->getEmail()->getMessageId()] = $emailUser;
        }

        return $existEmailUsers;
    }

    /**
     * Checks if the given folders types are comparable.
     * For example two "Sent" folders are comparable, "Inbox" and "Other" folders
     * are comparable as well, but "Inbox" and "Sent" folders are not comparable
     *
     * @param string $folderType1
     * @param string $folderType2
     *
     * @return bool
     */
    protected function isComparableFolders($folderType1, $folderType2)
    {
        if ($folderType1 === $folderType2) {
            return true;
        }
        if ($folderType1 === FolderType::OTHER) {
            $folderType1 = FolderType::INBOX;
        }
        if ($folderType2 === FolderType::OTHER) {
            $folderType2 = FolderType::INBOX;
        }

        return ($folderType1 === $folderType2);
    }

    /**
     * @param EmailOrigin $emailOrigin
     */
    protected function initEnv(EmailOrigin $emailOrigin)
    {
        $this->currentUser = $this->em->getRepository('OroEmailBundle:Mailbox')->findOneByOrigin($emailOrigin);
        if ($this->currentUser === null) {
            $this->currentUser = $emailOrigin->getOwner() ? $this->em->getReference(
                'Oro\Bundle\UserBundle\Entity\User',
                $emailOrigin->getOwner()->getId()
            ) : null;
        }
        $this->currentOrganization = $this->em->getReference(
            'Oro\Bundle\OrganizationBundle\Entity\Organization',
            $emailOrigin->getOrganization()->getId()
        );
    }

    /**
     * @return array
     */
    protected function entitiesToClear()
    {
        return [
            'Oro\Bundle\EmailBundle\Entity\Email',
            'Oro\Bundle\EmailBundle\Entity\EmailUser',
            'Oro\Bundle\EmailBundle\Entity\EmailRecipient',
            'Oro\Bundle\ImapBundle\Entity\ImapEmail',
            'Oro\Bundle\EmailBundle\Entity\EmailBody',
        ];
    }

    /**
     * Cleans doctrine's UOF to prevent:
     *  - "eating" too much memory
     *  - storing too many object which cause slowness of sync process
     * Tracks time when last batch was saved.
     * Calculates time between batch saves.
     *
     * @param bool             $isFolderSyncComplete
     * @param null|EmailFolder $folder
     */
    protected function cleanUp($isFolderSyncComplete = false, $folder = null)
    {
        $this->emailEntityBuilder->getBatch()->clear();

        /**
         * Clear entity manager.
         */
        $map = $this->entitiesToClear();
        foreach ($map as $entityClass) {
            $this->em->clear($entityClass);
        }

        /**
         * In case folder sync completed and batch save time exceeded limit - throws exception.
         */
        if ($isFolderSyncComplete
            && $folder != null
            && $this->dbBatchSaveTime > 0
            && $this->dbBatchSaveTime > self::DB_BATCH_TIME
        ) {
            throw new SyncFolderTimeoutException($folder->getOrigin()->getId(), $folder->getFullName());
        } elseif ($isFolderSyncComplete) {
            /**
             * In case folder sync completed without batch save time exceed - reset dbBatchSaveTime.
             */
            $this->dbBatchSaveTime = -1;
        } else {
            /**
             * After batch save - calculate time difference between batches
             */
            if ($this->dbBatchSaveTimestamp !== 0) {
                $this->dbBatchSaveTime = time() - $this->dbBatchSaveTimestamp;

                $this->logger->info(sprintf('Batch save time: "%d" seconds.', $this->dbBatchSaveTime));
            }
        }
        $this->dbBatchSaveTimestamp = time();
    }

    /**
     * Checks if recipient is a system-wide mailbox.
     *
     * @param integer     $mailboxId
     * @param EmailHeader $email
     *
     * @return bool
     */
    private function isMailboxRecipient($mailboxId, $email)
    {
        return $this->knownEmailAddressChecker->isAtLeastOneMailboxEmailAddress(
            $mailboxId,
            $email->getToRecipients(),
            $email->getCcRecipients(),
            $email->getBccRecipients()
        );
    }

    /**
     * Checks if sender is a system-wide mailbox.
     *
     * @param integer     $mailboxId
     * @param EmailHeader $email
     *
     * @return bool
     */
    private function isMailboxSender($mailboxId, $email)
    {
        return $this->knownEmailAddressChecker->isAtLeastOneMailboxEmailAddress(
            $mailboxId,
            $email->getFrom()
        );
    }
}
