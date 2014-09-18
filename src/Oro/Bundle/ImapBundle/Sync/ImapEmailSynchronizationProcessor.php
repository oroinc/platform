<?php

namespace Oro\Bundle\ImapBundle\Sync;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressChecker;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizationProcessor;
use Oro\Bundle\ImapBundle\Connector\Search\SearchQuery;
use Oro\Bundle\ImapBundle\Connector\Search\SearchQueryBuilder;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailFolderRepository;
use Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailRepository;
use Oro\Bundle\ImapBundle\Mail\Storage\Folder;
use Oro\Bundle\ImapBundle\Mail\Storage\Imap;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManager;
use Oro\Bundle\ImapBundle\Manager\DTO\Email;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ImapEmailSynchronizationProcessor extends AbstractEmailSynchronizationProcessor
{
    const EMAIL_ADDRESS_BATCH_SIZE = 100;
    const CLEANUP_EVERY_N_RUN      = 100;

    /** @var ImapEmailManager */
    protected $manager;

    /**
     * Constructor
     *
     * @param LoggerInterface          $log
     * @param EntityManager            $em
     * @param EmailEntityBuilder       $emailEntityBuilder
     * @param EmailAddressManager      $emailAddressManager
     * @param KnownEmailAddressChecker $knownEmailAddressChecker
     * @param ImapEmailManager         $manager
     */
    public function __construct(
        LoggerInterface $log,
        EntityManager $em,
        EmailEntityBuilder $emailEntityBuilder,
        EmailAddressManager $emailAddressManager,
        KnownEmailAddressChecker $knownEmailAddressChecker,
        ImapEmailManager $manager
    ) {
        parent::__construct($log, $em, $emailEntityBuilder, $emailAddressManager, $knownEmailAddressChecker);
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(EmailOrigin $origin, $syncStartTime)
    {
        // make sure that the entity builder is empty
        $this->emailEntityBuilder->clear();

        // get a list of emails belong to any object, for example an user or a contacts
        $emailAddressBatches = $this->getKnownEmailAddressBatches($origin->getSynchronizedAt());

        // iterate through all folders and do a synchronization of emails for each one
        $imapFolders = $this->syncFolders($origin);
        foreach ($imapFolders as $imapFolder) {
            $synchronizedAt = new \DateTime('now', new \DateTimeZone('UTC'));
            $folder         = $imapFolder->getFolder();

            // register the current folder in the entity builder
            $this->emailEntityBuilder->setFolder($folder);

            // ask an email server to select the current folder
            $folderName = $folder->getFullName();
            $this->manager->selectFolder($folderName);

            // sync emails in the current folder
            $this->log->notice(sprintf('Loading emails from "%s" folder ...', $folderName));
            foreach ($emailAddressBatches as $emailAddressBatch) {
                $needFullSync = $emailAddressBatch['needFullSync'] && !$folder->getSynchronizedAt();

                $this->syncEmails(
                    $imapFolder,
                    $this->getSearchQuery($folder, $needFullSync, $emailAddressBatch['items'])
                );
            }

            // update folder sync time
            $folder->setSynchronizedAt($synchronizedAt);
            $this->em->flush();
        }

        if ($origin->getSyncCount() > 0 && $origin->getSyncCount() % self::CLEANUP_EVERY_N_RUN == 0) {
            $this->cleanupOutdatedFolders($origin);
        }
    }

    /**
     * Delete outdated folders
     *
     * @param EmailOrigin $origin
     */
    protected function cleanupOutdatedFolders(EmailOrigin $origin)
    {
        $imapFolders = $this->em->getRepository('OroImapBundle:ImapEmailFolder')
            ->createQueryBuilder('if')
            ->select('if, folder')
            ->innerJoin('if.folder', 'folder')
            ->leftJoin('folder.emails', 'emails')
            ->where('folder.outdatedAt IS NULL AND emails.id IS NULL')
            ->andWhere('folder.origin = :origin')
            ->setParameter('origin', $origin)
            ->getQuery()
            ->getResult();

        /** @var ImapEmailFolder $imapFolder */
        foreach ($imapFolders as $imapFolder) {
            $this->log->notice(sprintf('CLEANUP: Removing "%s" folder...', $imapFolder->getFolder()->getFullName()));
            $this->em->remove($imapFolder);
            $this->em->remove($imapFolder->getFolder());
        }

        if (count($imapFolders) > 0) {
            $this->em->flush();
            $this->log->notice(sprintf('CLEANUP: Removed %d folders'));
        }
    }

    /**
     * Builds IMAP search query is used to find emails to be synchronized
     *
     * @param EmailFolder    $folder
     * @param bool           $needFullSync
     * @param EmailAddress[] $emailAddresses
     *
     * @return SearchQuery
     */
    protected function getSearchQuery(EmailFolder $folder, $needFullSync, array $emailAddresses)
    {
        $sqb = $this->manager->getSearchQueryBuilder();
        if (false == $needFullSync) {
            $sqb->sent($folder->getSynchronizedAt());
        }

        if ($folder->getType() === FolderType::SENT) {
            $sqb->openParenthesis();
            $this->addEmailAddressesToSearchQueryBuilder($sqb, 'to', $emailAddresses);
            $sqb->orOperator();
            $this->addEmailAddressesToSearchQueryBuilder($sqb, 'cc', $emailAddresses);

            // not all IMAP servers support search by BCC, for example imap-mail.outlook.com does not
            //$sqb->orOperator();
            //$this->addEmailAddressesToSearchQueryBuilder($sqb, 'bcc', $emailAddresses);

            $sqb->closeParenthesis();
        } else {
            $sqb->openParenthesis();
            $this->addEmailAddressesToSearchQueryBuilder($sqb, 'from', $emailAddresses);
            $sqb->closeParenthesis();
        }

        return $sqb->get();
    }

    /**
     * Adds the given email addresses to the search query.
     * Addresses are delimited by OR operator.
     *
     * @param SearchQueryBuilder $sqb
     * @param string             $addressType
     * @param EmailAddress[]     $addresses
     */
    protected function addEmailAddressesToSearchQueryBuilder(SearchQueryBuilder $sqb, $addressType, array $addresses)
    {
        for ($i = 0; $i < count($addresses); $i++) {
            if ($i > 0) {
                $sqb->orOperator();
            }
            $sqb->{$addressType}($addresses[$i]->getEmail());
        }
    }

    /**
     * Gets a list of email addresses which have an owner and splits them into batches
     *
     * @param \DateTime|null $lastSyncTime
     *
     * @return array of ['needFullSync' => true/false, 'items' => EmailAddress[]]
     */
    protected function getKnownEmailAddressBatches($lastSyncTime)
    {
        $batches    = array();
        $batchIndex = 0;
        $count      = 0;
        foreach ($this->getKnownEmailAddresses() as $emailAddress) {
            $needFullSync = !$lastSyncTime || $emailAddress->getUpdated() > $lastSyncTime;
            if ($count >= self::EMAIL_ADDRESS_BATCH_SIZE
                || (isset($batches[$batchIndex]) && $needFullSync !== $batches[$batchIndex]['needFullSync'])
            ) {
                $batchIndex++;
                $count = 0;
            }
            if ($count === 0) {
                $batches[$batchIndex] = array('needFullSync' => $needFullSync, 'items' => array());
            }
            $batches[$batchIndex]['items'][$count] = $emailAddress;
            $count++;
        }

        return $batches;
    }

    /**
     * Performs synchronization of folders
     *
     * @param EmailOrigin $origin
     *
     * @return ImapEmailFolder[] The list folders excluding outdated ones
     */
    protected function syncFolders(EmailOrigin $origin)
    {
        $imapFoldersToSync = [];

        $existingImapFolders = $this->getExistingImapFolders($origin);
        $srcFolders          = $this->getFolders();
        foreach ($srcFolders as $srcFolder) {
            $folderFullName = $srcFolder->getGlobalName();
            $uidValidity    = $this->getUidValidity($srcFolder);

            // check if the current folder already exist and has no changes,
            // if so, remove it from the list of existing folders
            $imapFolder = null;
            foreach ($existingImapFolders as $key => $existingImapFolder) {
                if ($existingImapFolder->getUidValidity() === $uidValidity
                    && $existingImapFolder->getFolder()->getFullName() === $folderFullName
                ) {
                    $imapFolder = $existingImapFolder;
                    unset($existingImapFolders[$key]);
                    break;
                }
            }

            // check if new folder need to be created
            if (!$imapFolder) {
                $this->log->notice(sprintf('Persisting "%s" folder ...', $folderFullName));

                $folder = new EmailFolder();
                $folder
                    ->setFullName($folderFullName)
                    ->setName($srcFolder->getLocalName())
                    ->setType($srcFolder->guessFolderType());
                $origin->addFolder($folder);
                $this->em->persist($folder);

                $imapFolder = new ImapEmailFolder();
                $imapFolder->setFolder($folder);
                $imapFolder->setUidValidity($uidValidity);
                $this->em->persist($imapFolder);

                $this->log->notice(sprintf('The "%s" folder was persisted.', $folderFullName));
            }

            // save folder to the list of folders to be synchronized
            $imapFoldersToSync[] = $imapFolder;
        }

        // mark the rest of existing folders as outdated
        foreach ($existingImapFolders as $imapFolder) {
            $this->log->notice(
                sprintf('Mark "%s" folder as outdated.', $imapFolder->getFolder()->getFullName())
            );
            $imapFolder->getFolder()->setOutdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
            $this->em->persist($imapFolder->getFolder());
        }

        $this->em->persist($origin);
        $this->em->flush();

        return $imapFoldersToSync;
    }

    /**
     * Gets the list of IMAP folders already stored in a database
     * The outdated folders are ignored
     *
     * @param EmailOrigin $origin
     *
     * @return ImapEmailFolder[]
     */
    protected function getExistingImapFolders(EmailOrigin $origin)
    {
        $this->log->notice('Loading existing folders ...');

        /** @var ImapEmailFolderRepository $repo */
        $repo        = $this->em->getRepository('OroImapBundle:ImapEmailFolder');
        $imapFolders = $repo->getFoldersByOrigin($origin);

        $this->log->notice(sprintf('Loaded %d existing folder(s).', count($imapFolders)));

        return $imapFolders;
    }

    /**
     * Gets all folders from IMAP server
     *
     * @return Folder[]
     */
    protected function getFolders()
    {
        $this->log->notice('Retrieving folders from an email server ...');

        $srcFolders = $this->manager->getFolders(null, true);

        $folders = [];
        foreach ($srcFolders as $srcFolder) {
            if (!$srcFolder->isSelectable()) {
                continue;
            }
            if ($srcFolder->hasFlag([Folder::FLAG_DRAFTS, Folder::FLAG_SPAM, Folder::FLAG_TRASH, Folder::FLAG_ALL])) {
                continue;
            }

            $folders[] = $srcFolder;
        }

        $this->log->notice(sprintf('Retrieved %d folder(s).', count($folders)));

        return $folders;
    }

    /**
     * Gets UIDVALIDITY of the given folder
     *
     * @param Folder $folder
     *
     * @return int
     */
    protected function getUidValidity(Folder $folder)
    {
        $this->manager->selectFolder($folder->getGlobalName());

        return $this->manager->getUidValidity();
    }

    /**
     * Performs synchronization of emails retrieved by the given search query in the given folder
     *
     * @param ImapEmailFolder $imapFolder
     * @param SearchQuery     $searchQuery
     */
    protected function syncEmails(ImapEmailFolder $imapFolder, SearchQuery $searchQuery)
    {
        $this->log->notice(sprintf('Query: "%s".', $searchQuery->convertToSearchString()));

        $emails = $this->manager->getEmails($searchQuery);

        $count = 0;
        $batch = array();
        foreach ($emails as $email) {
            $count++;
            $batch[] = $email;
            if ($count === self::DB_BATCH_SIZE) {
                $this->saveEmails($batch, $imapFolder);
                $count = 0;
                $batch = array();
            }
        }
        if ($count > 0) {
            $this->saveEmails($batch, $imapFolder);
        }
    }

    /**
     * Saves emails into the database
     *
     * @param Email[]         $emails
     * @param ImapEmailFolder $imapFolder
     */
    protected function saveEmails(array $emails, ImapEmailFolder $imapFolder)
    {
        $this->emailEntityBuilder->removeEmails();

        $folder        = $imapFolder->getFolder();
        $existingUids  = $this->getExistingUids($folder, $emails);
        $isMultiFolder = $this->manager->hasCapability(Imap::CAPABILITY_MSG_MULTI_FOLDERS);

        $existingImapEmails = $this->getExistingImapEmails(
            $folder->getOrigin(),
            $this->getNewMessageIds($emails, $existingUids),
            $isMultiFolder
        );

        /** @var ImapEmail[] $newImapEmails */
        $newImapEmails = [];

        foreach ($emails as $email) {
            if (in_array($email->getId()->getUid(), $existingUids)) {
                $this->log->notice(
                    sprintf(
                        'Skip "%s" (UID: %d) email, because it is already synchronised.',
                        $email->getSubject(),
                        $email->getId()->getUid()
                    )
                );
                continue;
            }

            /** @var ImapEmail[] $relatedExistingImapEmails */
            $relatedExistingImapEmails = array_filter(
                $existingImapEmails,
                function (ImapEmail $imapEmail) use ($email) {
                    return $imapEmail->getEmail()->getMessageId() === $email->getMessageId();
                }
            );

            $existingImapEmail = $isMultiFolder
                ? null
                : $this->findExistingImapEmail($relatedExistingImapEmails, $folder->getType());
            if ($existingImapEmail) {
                $this->moveEmailToOtherFolder($existingImapEmail, $imapFolder, $email->getId()->getUid());
            } else {
                $this->log->notice(
                    sprintf('Persisting "%s" email (UID: %d) ...', $email->getSubject(), $email->getId()->getUid())
                );
                $imapEmail       = $this->createImapEmail(
                    $email->getId()->getUid(),
                    $this->addEmail($email, $folder),
                    $imapFolder
                );
                $newImapEmails[] = $imapEmail;
                $this->em->persist($imapEmail);
                $this->log->notice(sprintf('The "%s" email was persisted.', $email->getSubject()));
            }

            $this->removeEmailFromOutdatedFolders($relatedExistingImapEmails);
        }

        $this->emailEntityBuilder->getBatch()->persist($this->em);

        // update references if needed
        $changes = $this->emailEntityBuilder->getBatch()->getChanges();
        foreach ($newImapEmails as $imapEmail) {
            foreach ($changes as $change) {
                if ($change['old'] instanceof EmailEntity && $imapEmail->getEmail() === $change['old']) {
                    $imapEmail->setEmail($change['new']);
                }
            }
        }

        $this->em->flush();
    }

    /**
     * Tries to find IMAP email in the given list of related IMAP emails
     * This method returns ImapEmail object only if exactly one email is found
     * and this email is located in the comparable folder {@see isComparableFolders()}
     *
     * @param ImapEmail[] $imapEmails
     * @param string      $folderType
     *
     * @return ImapEmail|null
     */
    protected function findExistingImapEmail(array $imapEmails, $folderType)
    {
        if (empty($imapEmails)) {
            return null;
        }
        if (count($imapEmails) === 1) {
            /** @var ImapEmail $imapEmail */
            $imapEmail = reset($imapEmails);
            return $this->isComparableFolders($folderType, $imapEmail->getImapFolder()->getFolder()->getType())
                ? $imapEmail
                : null;
        }

        /** @var ImapEmail[] $outdatedImapEmails */
        $activeImapEmails = array_filter(
            $imapEmails,
            function (ImapEmail $imapEmail) use ($folderType) {
                return
                    !$imapEmail->getImapFolder()->getFolder()->isOutdated()
                    && $this->isComparableFolders($folderType, $imapEmail->getImapFolder()->getFolder()->getType());
            }
        );

        return count($activeImapEmails) === 1
            ? reset($activeImapEmails)
            : null;
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
        if ($folderType1 === $folderType2) {
            return true;
        }

        return false;
    }

    /**
     * Removes email from all outdated folders
     *
     * @param ImapEmail[] $imapEmails The list of all related IMAP emails
     */
    protected function removeEmailFromOutdatedFolders(array $imapEmails)
    {
        /** @var ImapEmail[] $outdatedImapEmails */
        $outdatedImapEmails = array_filter(
            $imapEmails,
            function (ImapEmail $imapEmail) {
                return $imapEmail->getImapFolder()->getFolder()->isOutdated();
            }
        );
        foreach ($outdatedImapEmails as $imapEmail) {
            $this->removeImapEmailReference($imapEmail);
        }
    }

    /**
     * Moves an email to another folder
     *
     * @param ImapEmail       $imapEmail
     * @param ImapEmailFolder $newImapFolder
     * @param int             $newUid
     */
    protected function moveEmailToOtherFolder(ImapEmail $imapEmail, ImapEmailFolder $newImapFolder, $newUid)
    {
        $this->log->notice(
            sprintf(
                'Move "%s" (UID: %d) email from "%s" to "%s". New UID: %d.',
                $imapEmail->getEmail()->getSubject(),
                $imapEmail->getUid(),
                $imapEmail->getImapFolder()->getFolder()->getFullName(),
                $newImapFolder->getFolder()->getFullName(),
                $newUid
            )
        );

        $imapEmail->getEmail()->removeFolder($imapEmail->getImapFolder()->getFolder());
        $imapEmail->getEmail()->addFolder($newImapFolder->getFolder());
        $imapEmail->setImapFolder($newImapFolder);
        $imapEmail->setUid($newUid);
    }

    /**
     * Removes an email from a folder linked to the given IMAP email object
     *
     * @param ImapEmail $imapEmail
     */
    protected function removeImapEmailReference(ImapEmail $imapEmail)
    {
        $this->log->notice(
            sprintf(
                'Remove "%s" (UID: %d) email from "%s".',
                $imapEmail->getEmail()->getSubject(),
                $imapEmail->getUid(),
                $imapEmail->getImapFolder()->getFolder()->getFullName()
            )
        );
        $imapEmail->getEmail()->removeFolder($imapEmail->getImapFolder()->getFolder());
        $this->em->remove($imapEmail);
    }

    /**
     * Creates email entity and register it in the email entity batch processor
     *
     * @param Email       $email
     * @param EmailFolder $folder
     *
     * @return EmailEntity
     */
    protected function addEmail(Email $email, EmailFolder $folder)
    {
        $emailEntity = $this->emailEntityBuilder->email(
            $email->getSubject(),
            $email->getFrom(),
            $email->getToRecipients(),
            $email->getSentAt(),
            $email->getReceivedAt(),
            $email->getInternalDate(),
            $email->getImportance(),
            $email->getCcRecipients(),
            $email->getBccRecipients()
        );
        $emailEntity
            ->addFolder($folder)
            ->setMessageId($email->getMessageId())
            ->setXMessageId($email->getXMessageId())
            ->setXThreadId($email->getXThreadId());

        return $emailEntity;
    }

    /**
     * Gets the list of UIDs of emails already exist in a database
     *
     * @param EmailFolder $folder
     * @param Email[]     $emails
     *
     * @return int[] array if UIDs
     */
    protected function getExistingUids(EmailFolder $folder, array $emails)
    {
        if (empty($emails)) {
            return [];
        }

        $uids = array_map(
            function ($el) {
                /** @var Email $el */
                return $el->getId()->getUid();
            },
            $emails
        );

        /** @var ImapEmailRepository $repo */
        $repo = $this->em->getRepository('OroImapBundle:ImapEmail');

        return $repo->getExistingUids($folder, $uids);
    }

    /**
     * @param EmailOrigin $origin
     * @param string[]    $messageIds
     * @param bool        $outdatedOnly
     *
     * @return ImapEmail[]
     */
    protected function getExistingImapEmails(EmailOrigin $origin, array $messageIds, $outdatedOnly)
    {
        if (empty($messageIds)) {
            return [];
        }

        /** @var ImapEmailRepository $repo */
        $repo = $this->em->getRepository('OroImapBundle:ImapEmail');

        return $outdatedOnly
            ? $repo->getOutdatedEmailsByMessageIds($origin, $messageIds)
            : $repo->getEmailsByMessageIds($origin, $messageIds);
    }

    /**
     * Gets the list of Message-IDs for emails with the given UIDs
     *
     * @param Email[] $emails
     * @param array   $existingUids
     *
     * @return string[]
     */
    protected function getNewMessageIds(array $emails, array $existingUids)
    {
        $result = [];
        foreach ($emails as $email) {
            if (!in_array($email->getId()->getUid(), $existingUids)) {
                $result[] = $email->getMessageId();
            }

        }

        return $result;
    }

    /**
     * Creates new ImapEmail object
     *
     * @param int             $uid
     * @param EmailEntity     $email
     * @param ImapEmailFolder $imapFolder
     *
     * @return ImapEmail
     */
    protected function createImapEmail($uid, EmailEntity $email, ImapEmailFolder $imapFolder)
    {
        $imapEmail = new ImapEmail();
        $imapEmail
            ->setUid($uid)
            ->setEmail($email)
            ->setImapFolder($imapFolder);

        return $imapEmail;
    }
}
