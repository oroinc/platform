<?php

namespace Oro\Bundle\ImapBundle\Sync;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizationProcessor;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressChecker;

use Oro\Bundle\ImapBundle\Connector\Search\SearchQuery;
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
    /** Determines how many emails can be loaded from IMAP server at once */
    const READ_BATCH_SIZE = 100;

    /** Determines how often "Processed X of N emails" hint should be added to a log */
    const READ_HINT_COUNT = 500;

    /** Determines how often the clearing of outdated folders routine should be executed */
    const CLEANUP_EVERY_N_RUN = 100;

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

        // iterate through all folders and do a synchronization of emails for each one
        $imapFolders = $this->syncFolders($origin);
        foreach ($imapFolders as $imapFolder) {
            $folder = $imapFolder->getFolder();

            // ask an email server to select the current folder
            $folderName = $folder->getFullName();
            $this->manager->selectFolder($folderName);

            // register the current folder in the entity builder
            $this->emailEntityBuilder->setFolder($folder);

            // build a search query
            $sqb = $this->manager->getSearchQueryBuilder();
            if ($origin->getSynchronizedAt() && $folder->getSynchronizedAt()) {
                if ($folder->getType() === FolderType::SENT) {
                    $sqb->sent($folder->getSynchronizedAt());
                } else {
                    $sqb->received($folder->getSynchronizedAt());
                }
            }

            // sync emails using this search query
            $lastSynchronizedAt = $this->syncEmails($imapFolder, $sqb->get());

            // update synchronization date for the current folder
            $folder->setSynchronizedAt($lastSynchronizedAt > $syncStartTime ? $lastSynchronizedAt : $syncStartTime);
            $this->em->flush($folder);
        }

        // run removing of empty outdated folders every N synchronizations
        if ($origin->getSyncCount() > 0 && $origin->getSyncCount() % self::CLEANUP_EVERY_N_RUN == 0) {
            $this->cleanupOutdatedFolders($origin);
        }
    }

    /**
     * Deletes all empty outdated folders
     *
     * @param EmailOrigin $origin
     */
    protected function cleanupOutdatedFolders(EmailOrigin $origin)
    {
        $this->log->notice('Removing empty outdated folders ...');

        /** @var ImapEmailFolderRepository $repo */
        $repo        = $this->em->getRepository('OroImapBundle:ImapEmailFolder');
        $imapFolders = $repo->getEmptyOutdatedFoldersByOrigin($origin);
        $folders     = new ArrayCollection();

        foreach ($imapFolders as $imapFolder) {
            $this->log->notice(sprintf('Remove "%s" folder.', $imapFolder->getFolder()->getFullName()));

            if (!$folders->contains($imapFolder->getFolder())) {
                $folders->add($imapFolder->getFolder());
            }

            $this->em->remove($imapFolder);
        }

        foreach ($folders as $folder) {
            $this->em->remove($folder);
        }

        if (count($imapFolders) > 0) {
            $this->em->flush();
            $this->log->notice(sprintf('Removed %d folder(s).', count($imapFolders)));
        }
    }

    /**
     * Performs synchronization of folders
     *
     * @param EmailOrigin $origin
     *
     * @return ImapEmailFolder[] The list of folders for which emails need to be synchronized
     */
    protected function syncFolders(EmailOrigin $origin)
    {
        $folders = [];

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
            $folders[] = $imapFolder;
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

        return $folders;
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

        $this->log->notice(sprintf('Loaded %d folder(s).', count($imapFolders)));

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
     *
     * @return \DateTime The max sent date
     */
    protected function syncEmails(ImapEmailFolder $imapFolder, SearchQuery $searchQuery)
    {
        $folder             = $imapFolder->getFolder();
        $folderType         = $folder->getType();
        $lastSynchronizedAt = $folder->getSynchronizedAt();

        $this->log->notice(sprintf('Loading emails from "%s" folder ...', $folder->getFullName()));
        $this->log->notice(sprintf('Query: "%s".', $searchQuery->convertToSearchString()));

        $emails = $this->manager->getEmails($searchQuery);
        $emails->setBatchSize(self::READ_BATCH_SIZE);
        $emails->setBatchCallback(
            function ($batch) use ($folderType) {
                $this->registerEmailsInKnownEmailAddressChecker($batch, $folderType);
            }
        );
        $this->log->notice(sprintf('Found %d email(s).', $emails->count()));

        $count     = 0;
        $processed = 0;
        $batch     = [];
        /** @var Email $email */
        foreach ($emails as $email) {
            $processed++;
            if ($processed % self::READ_HINT_COUNT === 0) {
                $this->log->notice(sprintf('Processed %d of %d emails ...', $processed, $emails->count()));
            }

            if (!$this->isApplicableEmail($email, $folderType)) {
                continue;
            }

            if ($email->getSentAt() > $lastSynchronizedAt) {
                $lastSynchronizedAt = $email->getSentAt();
            }

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

        return $lastSynchronizedAt;
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

            $existingImapEmail = $this->findExistingImapEmail(
                $relatedExistingImapEmails,
                $folder->getType(),
                $isMultiFolder
            );
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
     * @param bool        $outdatedOnly
     *
     * @return ImapEmail|null
     */
    protected function findExistingImapEmail(array $imapEmails, $folderType, $outdatedOnly)
    {
        if (empty($imapEmails)) {
            return null;
        }
        if (count($imapEmails) === 1) {
            /** @var ImapEmail $imapEmail */
            $imapEmail = reset($imapEmails);
            if ($outdatedOnly && !$imapEmail->getImapFolder()->getFolder()->isOutdated()) {
                return null;
            }
            if (!$this->isComparableFolders($folderType, $imapEmail->getImapFolder()->getFolder()->getType())) {
                return null;
            }

            return $imapEmail;
        }

        /** @var ImapEmail[] $filteredImapEmails */
        $filteredImapEmails = array_filter(
            $imapEmails,
            function (ImapEmail $imapEmail) use ($folderType, $outdatedOnly) {
                return
                    !($outdatedOnly xor $imapEmail->getImapFolder()->getFolder()->isOutdated())
                    && $this->isComparableFolders($folderType, $imapEmail->getImapFolder()->getFolder()->getType());
            }
        );

        return count($filteredImapEmails) === 1
            ? reset($filteredImapEmails)
            : null;
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
     * Gets the list of IMAP emails by Message-ID
     *
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
