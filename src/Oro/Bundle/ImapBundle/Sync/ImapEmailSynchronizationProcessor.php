<?php

namespace Oro\Bundle\ImapBundle\Sync;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizationProcessor;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerInterface;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailFolderRepository;
use Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailRepository;
use Oro\Bundle\ImapBundle\Mail\Protocol\Exception\InvalidEmailFormatException;
use Oro\Bundle\ImapBundle\Mail\Storage\Exception\UnsupportException;
use Oro\Bundle\ImapBundle\Mail\Storage\Exception\UnselectableFolderException;
use Oro\Bundle\ImapBundle\Manager\ImapEmailIterator;
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

    /** Time limit to sync origin in seconds */
    const MAX_ORIGIN_SYNC_TIME = 60;

    /** @var ImapEmailManager */
    protected $manager;

    /** @var ImapEmailRemoveManager */
    protected $removeManager;

    /**
     * Constructor
     *
     * @param EntityManager $em
     * @param EmailEntityBuilder $emailEntityBuilder
     * @param KnownEmailAddressCheckerInterface $knownEmailAddressChecker
     * @param ImapEmailManager $manager
     * @param ImapEmailRemoveManager $removeManager
     */
    public function __construct(
        EntityManager $em,
        EmailEntityBuilder $emailEntityBuilder,
        KnownEmailAddressCheckerInterface $knownEmailAddressChecker,
        ImapEmailManager $manager,
        ImapEmailRemoveManager $removeManager
    ) {
        parent::__construct($em, $emailEntityBuilder, $knownEmailAddressChecker);
        $this->manager = $manager;
        $this->removeManager = $removeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(EmailOrigin $origin, $syncStartTime)
    {
        // make sure that the entity builder is empty
        $this->emailEntityBuilder->clear();

        $this->initEnv($origin);
        $processStartTime = time();
        // iterate through all folders enabled for sync and do a synchronization of emails for each one
        $imapFolders = $this->getSyncEnabledImapFolders($origin);
        foreach ($imapFolders as $imapFolder) {
            $folder = $imapFolder->getFolder();

            // ask an email server to select the current folder
            $folderName = $folder->getFullName();
            try {
                $this->manager->selectFolder($folderName);
                $this->logger->info(sprintf('The folder "%s" is selected.', $folderName));

                // register the current folder in the entity builder
                $this->emailEntityBuilder->setFolder($folder);

                // sync emails using this search query
                $lastSynchronizedAt = $this->syncEmails($origin, $imapFolder);
                $folder->setSynchronizedAt($lastSynchronizedAt > $syncStartTime ? $lastSynchronizedAt : $syncStartTime);

                $startDate = $folder->getSynchronizedAt();
                $checkStartDate = clone $startDate;
                $checkStartDate->modify('-6 month');

                // set seen flags from previously synchronized emails
                $this->checkFlags($imapFolder, $checkStartDate);

                $this->removeManager->removeRemotelyRemovedEmails($imapFolder, $folder, $this->manager);

            } catch (UnselectableFolderException $e) {
                $folder->setSyncEnabled(false);
                $this->logger->info(
                    sprintf('The folder "%s" cannot be selected and was skipped and disabled.', $folderName)
                );
            } catch (InvalidEmailFormatException $e) {
                $folder->setSyncEnabled(false);
                $this->logger->info(
                    sprintf('The folder "%s" has unsupported email format and was skipped and disabled.', $folderName)
                );
            }
            $this->em->flush($folder);

            $this->cleanUp(true, $imapFolder->getFolder());

            $processSpentTime = time() - $processStartTime;

            if (false === $this->getSettings()->isForceMode() && $processSpentTime > self::MAX_ORIGIN_SYNC_TIME) {
                break;
            }
        }

        // run removing of empty outdated folders every N synchronizations
        if ($origin->getSyncCount() > 0 && $origin->getSyncCount() % self::CLEANUP_EVERY_N_RUN == 0) {
            $this->removeManager->cleanupOutdatedFolders($origin);
        }
    }

    /**
     * @param ImapEmailFolder $imapFolder
     * @param \DateTime $startDate
     */
    protected function checkFlags(ImapEmailFolder $imapFolder, $startDate)
    {
        try {
            $uids = $this->manager->getUnseenEmailUIDs($startDate);

            $emailImapRepository = $this->em->getRepository('OroImapBundle:ImapEmail');
            $emailUserRepository = $this->em->getRepository('OroEmailBundle:EmailUser');

            $ids = $emailImapRepository->getEmailUserIdsByUIDs($uids, $imapFolder->getFolder(), $startDate);
            $invertedIds = $emailUserRepository->getInvertedIdsFromFolder($ids, $imapFolder->getFolder(), $startDate);

            $emailUserRepository->setEmailUsersSeen($ids, false);
            $emailUserRepository->setEmailUsersSeen($invertedIds, true);
        } catch (UnsupportException $e) {
            $this->logger->info(sprintf('Seen update unsupported - "%s"', $imapFolder->getFolder()->getOrigin()));
        }
    }

    /**
     * Performs synchronization of emails retrieved by the given search query in the given folder
     *
     * @param EmailOrigin $origin
     * @param ImapEmailFolder $imapFolder
     *
     * @return \DateTime The max sent date
     */
    protected function syncEmails(EmailOrigin $origin, ImapEmailFolder $imapFolder)
    {
        $folder             = $imapFolder->getFolder();
        $lastSynchronizedAt = $folder->getSynchronizedAt();
        $emails = $this->getEmailIterator($origin, $imapFolder, $folder);
        $count = $processed = $invalid = $totalInvalid = 0;
        $emails->setIterationOrder(true);
        $emails->setBatchSize(self::READ_BATCH_SIZE);
        $emails->setConvertErrorCallback(
            function (\Exception $e) use (&$invalid) {
                $invalid++;
                $this->logger->error(
                    sprintf('Error occurred while trying to process email: %s', $e->getMessage()),
                    ['exception' => $e]
                );
            }
        );

        $this->logger->info(sprintf('Found %d email(s).', $emails->count()));

        $batch = [];
        /** @var Email $email */
        foreach ($emails as $email) {
            $processed++;
            if ($processed % self::READ_HINT_COUNT === 0) {
                $this->logger->info(
                    sprintf(
                        'Processed %d of %d emails.%s',
                        $processed,
                        $emails->count(),
                        $invalid === 0 ? '' : sprintf(' Detected %d invalid email(s).', $invalid)
                    )
                );
                $totalInvalid += $invalid;
                $invalid = 0;
            }

            if ($email->getSentAt() > $lastSynchronizedAt) {
                $lastSynchronizedAt = $email->getSentAt();
            }

            $count++;
            $batch[] = $email;
            if ($count === self::DB_BATCH_SIZE) {
                $this->saveEmails(
                    $batch,
                    $imapFolder
                );
                $count = 0;
                $batch = [];
            }
        }
        if ($count > 0) {
            $this->saveEmails(
                $batch,
                $imapFolder
            );
        }

        $totalInvalid += $invalid;
        if ($totalInvalid > 0) {
            $this->logger->warning(
                sprintf('Detected %d invalid email(s) in "%s" folder.', $totalInvalid, $folder->getFullName())
            );
        }

        return $lastSynchronizedAt;
    }

    /**
     * Saves emails into the database
     *
     * @param Email[]         $emails
     * @param ImapEmailFolder $imapFolder
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function saveEmails(array $emails, ImapEmailFolder $imapFolder)
    {
        $this->emailEntityBuilder->removeEmails();

        $folder        = $imapFolder->getFolder();
        $existingUids  = $this->getExistingUids($folder, $emails);
        $messageIds         = $this->getMessageIds($emails);
        $existingImapEmails = $this->getExistingImapEmails($folder->getOrigin(), $messageIds);
        $existingEmailUsers = $this->getExistingEmailUsers($folder, $messageIds);
        /** @var ImapEmail[] $newImapEmails */
        $newImapEmails = [];
        foreach ($emails as $email) {
            if (!$this->checkOnOldEmailForMailbox($folder, $email, $folder->getOrigin()->getMailbox())) {
                continue;
            }

            if ($this->checkToSkipSyncEmail($email, $existingUids)) {
                continue;
            }

            /** @var ImapEmail[] $relatedExistingImapEmails */
            $relatedExistingImapEmails = array_filter(
                $existingImapEmails,
                function (ImapEmail $imapEmail) use ($email) {
                    return $imapEmail->getEmail()->getMessageId() === $email->getMessageId();
                }
            );

            try {
                if (!isset($existingEmailUsers[$email->getMessageId()])) {
                    $emailUser = $this->addEmailUser(
                        $email,
                        $folder,
                        $email->hasFlag("\\Seen"),
                        $this->currentUser,
                        $this->currentOrganization
                    );
                } else {
                    $emailUser = $existingEmailUsers[$email->getMessageId()];
                    if (!$emailUser->getFolders()->contains($folder)) {
                        $emailUser->addFolder($folder);
                    }
                }

                if (false === $this->getSettings()->isForceMode()
                    || (true  === $this->getSettings()->isForceMode() && count($relatedExistingImapEmails) === 0)
                ) {
                    $imapEmail = $this->createImapEmail($email->getId()->getUid(), $emailUser->getEmail(), $imapFolder);
                    $newImapEmails[] = $imapEmail;
                    $this->em->persist($imapEmail);
                    $this->logger->notice(
                        sprintf(
                            'The "%s" (UID: %d) email was persisted.',
                            $email->getSubject(),
                            $email->getId()->getUid()
                        )
                    );
                }
            } catch (\Exception $e) {
                $this->logger->warning(
                    sprintf(
                        'Failed to persist "%s" (UID: %d) email. Error: %s',
                        $email->getSubject(),
                        $email->getId()->getUid(),
                        $e->getMessage()
                    )
                );
            }

            $this->removeManager->removeEmailFromOutdatedFolders($relatedExistingImapEmails);
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

        $this->cleanUp();
    }

    /**
     * Check allowing to save email by date
     *
     * @param EmailFolder $folder
     * @param Email $email
     * @param Mailbox $mailbox
     *
     * @return bool
     */
    protected function checkOnOldEmailForMailbox(EmailFolder $folder, Email $email, $mailbox)
    {
        /**
         * @description Will select max of those dates because emails in folder `sent` could have no received date
         *              or same date.
         */
        $dateForCheck = max($email->getReceivedAt(), $email->getSentAt());

        if ($mailbox && $folder->getSyncStartDate() > $dateForCheck) {
            $this->logger->info(
                sprintf(
                    'Skip "%s" (UID: %d) email, because it was sent earlier than the start synchronization is set',
                    $email->getSubject(),
                    $email->getId()->getUid()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Check the email was synced by Uid or wasn't.
     *
     * @param Email $email
     * @param array $existingUids
     *
     * @return bool
     */
    protected function checkOnExistsSavedEmail(Email $email, array $existingUids)
    {
        if (in_array($email->getId()->getUid(), $existingUids)) {
            return true;
        }

        return false;
    }

    /**
     * Check allowing to save email by uid
     *
     * @param Email $email
     * @param array $existingUids
     *
     * @return bool
     */
    protected function checkToSkipSyncEmail($email, $existingUids)
    {
        $existsSavedEmail = $this->checkOnExistsSavedEmail($email, $existingUids);

        $skipSync = false;

        if ($existsSavedEmail) {
            $msg = 'Skip "%s" (UID: %d) email, because it is already synchronised.';
            $skipSync = true;

            if ($this->getSettings()->isForceMode()) {
                $msg = null;
                if ($this->getSettings()->needShowMessage()) {
                    $msg = 'Sync "%s" (UID: %d) email, because force mode is enabled.';
                }

                $skipSync = false;
            }

            if ($msg) {
                $this->logger->info(
                    sprintf(
                        $msg,
                        $email->getSubject(),
                        $email->getId()->getUid()
                    )
                );
            }
        }

        return $skipSync;
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
     *
     * @return ImapEmail[]
     */
    protected function getExistingImapEmails(EmailOrigin $origin, array $messageIds)
    {
        if (empty($messageIds)) {
            return [];
        }
        /** @var ImapEmailRepository $repo */
        $repo = $this->em->getRepository('OroImapBundle:ImapEmail');

        return $repo->getEmailsByMessageIds($origin, $messageIds);
    }

    /**
     * Gets the list of Message-IDs for emails
     *
     * @param Email[] $emails
     *
     * @return string[]
     */
    protected function getMessageIds(array $emails)
    {
        $result = [];
        foreach ($emails as $email) {
            $result[] = $email->getMessageId();
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

    /**
     * Get email ids and create iterator
     *
     * @param EmailOrigin $origin
     * @param ImapEmailFolder $imapFolder
     * @param EmailFolder $folder
     *
     * @return ImapEmailIterator
     */
    protected function getEmailIterator(
        EmailOrigin $origin,
        ImapEmailFolder $imapFolder,
        EmailFolder $folder
    ) {
        $lastUid = null;
        if (false === $this->getSettings()->isForceMode()) {
            $lastUid = $this->em->getRepository('OroImapBundle:ImapEmail')->findLastUidByFolder($imapFolder);
        }

        if (!$lastUid && $origin->getMailbox() && $folder->getSyncStartDate()) {
            $emails = $this->initialMailboxSync($folder);
        } else {
            $this->logger->info(sprintf('Previous max email UID "%s"', $lastUid));
            $emails = $this->manager->getEmailsUidBased($lastUid);
        }

        return $emails;
    }

    /**
     * First system mailbox sync from sync start date
     *
     * @param EmailFolder $folder
     *
     * @return ImapEmailIterator
     */
    protected function initialMailboxSync(EmailFolder $folder)
    {
        // build search query for emails sync
        $sqb = $this->manager->getSearchQueryBuilder();
        if ($folder->getType() === FolderType::SENT) {
            $sqb->sent($folder->getSyncStartDate());
        } else {
            $sqb->received($folder->getSyncStartDate());
        }
        $searchQuery = $sqb->get();
        $this->logger->info(sprintf('Loading emails from "%s" folder ...', $folder->getFullName()));
        $this->logger->info(sprintf('Query: "%s".', $searchQuery->convertToSearchString()));
        $emails = $this->manager->getEmails($searchQuery);

        return $emails;
    }

    /**
     * Gets the list of IMAP folders enabled for sync
     * The outdated folders are ignored
     *
     * @param EmailOrigin $origin
     *
     * @return ImapEmailFolder[]
     */
    protected function getSyncEnabledImapFolders(EmailOrigin $origin)
    {
        $this->logger->info('Get folders enabled for sync...');

        /** @var ImapEmailFolderRepository $repo */
        $repo        = $this->em->getRepository('OroImapBundle:ImapEmailFolder');
        $imapFolders = $repo->getFoldersByOrigin($origin, false, EmailFolder::SYNC_ENABLED_TRUE);

        $this->logger->info(sprintf('Got %d folder(s).', count($imapFolders)));

        return $imapFolders;
    }
}
