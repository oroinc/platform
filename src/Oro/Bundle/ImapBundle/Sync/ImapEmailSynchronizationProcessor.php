<?php

namespace Oro\Bundle\ImapBundle\Sync;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Psr\Log\LoggerInterface;

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
use Oro\Bundle\ImapBundle\Mail\Storage\Folder;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManager;
use Oro\Bundle\ImapBundle\Manager\DTO\Email;

class ImapEmailSynchronizationProcessor extends AbstractEmailSynchronizationProcessor
{
    const EMAIL_ADDRESS_BATCH_SIZE = 100;

    /**
     * @var ImapEmailManager
     */
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

        $imapFolders = $this->syncFolders($origin);

        // iterate through all folders and do a synchronization of emails for each one
        foreach ($imapFolders as $imapFolder) {
            $folder = $imapFolder->getFolder();

            // register the current folder in the entity builder
            $this->emailEntityBuilder->setFolder($folder);

            // ask an email server to select the current folder
            $folderName = $folder->getFullName();

            $this->log->notice(sprintf('Loading emails from "%s" folder ...', $folderName));
            foreach ($emailAddressBatches as $emailAddressBatch) {
                $needFullSync = $emailAddressBatch['needFullSync'] && !$folder->getSynchronizedAt();
                
                $this->loadEmails(
                    $imapFolder,
                    $this->getSearchQuery($folder, $needFullSync, $emailAddressBatch['items'])
                );
            }
        }
    }

    /**
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

        if ($folder->getType() === EmailFolder::SENT) {
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
     * @return array
     *                 key = index
     *                 value = array
     *                 'needFullSync' => true/false
     *                 'items' => EmailAddress[]
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
     * Sync folders and return IMAP folders
     *
     * @param EmailOrigin $origin
     *
     * @return ImapEmailFolder[]
     */
    protected function syncFolders(EmailOrigin $origin)
    {
        // load existing folders for $origin
        $this->log->notice('Loading existing folders ...');

        $imapFolders = $this->em
            ->getRepository('OroImapBundle:ImapEmailFolder')
            ->getFoldersByOrigin($origin);

        $this->log->notice(sprintf('Loaded %d existing folder(s).', count($imapFolders)));

        // sync
        $srcFolders = $this->loadSourceFolders();
        $processedIds = [];

        foreach ($srcFolders as $uidValidity => $srcFolder) {
            switch (true) {
                case $srcFolder->hasFlag(Folder::FLAG_INBOX):
                    $type = EmailFolder::INBOX;
                    break;
                case $srcFolder->hasFlag(Folder::FLAG_SENT):
                    $type = EmailFolder::SENT;
                    break;
                default:
                    $type = EmailFolder::OTHER;
            }

            $globalName         = $srcFolder->getGlobalName();
            $existingFolder     = $this->getFolderByGlobalName($imapFolders, $globalName);
            $isUidValidityEqual = $existingFolder && $existingFolder->getUidValidity() == $uidValidity;

            if ($existingFolder && $isUidValidityEqual) {
                $processedIds[] = $existingFolder->getId();
                // no changes in folder
                continue;
            }

            $this->log->notice(sprintf('Persisting "%s" folder ...', $globalName));

            $folder = new EmailFolder();
            $folder
                ->setFullName($globalName)
                ->setName($srcFolder->getLocalName())
                ->setType($type);

            $origin->addFolder($folder);
            $this->em->persist($folder);

            $imapFolder = new ImapEmailFolder();
            $imapFolder->setFolder($folder);
            $imapFolder->setUidValidity($uidValidity);

            $imapFolders[] = $imapFolder;
            $this->em->persist($imapFolder);

            $this->log->notice(sprintf('The "%s" folder was persisted.', $globalName));
        }

        /** @var ImapEmailFolder $imapFolder */
        foreach ($imapFolders as $imapFolder) {
            // mark as outdated not processed folder
            $isOutdated = $imapFolder->getId() && false === in_array($imapFolder->getId(), $processedIds);
            if ($isOutdated) {
                $imapFolder->getFolder()->setOutdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
                $this->em->persist($imapFolder->getFolder());
            }
        }

        $this->em->persist($origin);
        $this->em->flush();

        return $imapFolders;
    }

    /**
     * Get folders from mail server
     *
     * @return array|Folder[]
     */
    protected function loadSourceFolders()
    {
        $this->log->notice('Retrieving folders from an email server ...');
        $srcFolders = $this->manager->getFolders(null, true);

        $filteredFolders = [];
        foreach ($srcFolders as $srcFolder) {
            $isTrashFolder = $srcFolder->hasFlag(
                [Folder::FLAG_DRAFTS, Folder::FLAG_SPAM, Folder::FLAG_TRASH, Folder::FLAG_ALL]
            );
            if ($isTrashFolder || false === $srcFolder->isSelectable()) {
                continue;
            }

            $this->manager->selectFolder($srcFolder->getGlobalName());
            $uidValidity = $this->manager->getUidValidity();

            $filteredFolders[$uidValidity] = $srcFolder;
        }

        $this->log->notice(sprintf('Retrieved %d folder(s).', count($filteredFolders)));

        return $filteredFolders;
    }

    /**
     * Checks if the folder exists in the given list
     *
     * @param ImapEmailFolder[] $imapFolders
     * @param string            $folderGlobalName
     *
     * @return bool|ImapEmailFolder
     */
    protected function getFolderByGlobalName(array &$imapFolders, $folderGlobalName)
    {
        $folder = false;

        foreach ($imapFolders as $imapFolder) {
            if ($imapFolder->getFolder()->getFullName() === $folderGlobalName) {
                $folder = $imapFolder;
                break;
            }
        }

        return $folder;
    }

    /**
     * Loads emails from an email server and save them into the database
     *
     * @param ImapEmailFolder $imapFolder
     * @param SearchQuery     $searchQuery
     */
    protected function loadEmails(ImapEmailFolder $imapFolder, SearchQuery $searchQuery)
    {
        $this->log->notice(sprintf('Query: "%s".', $searchQuery->convertToSearchString()));

        $folder = $imapFolder->getFolder();
        $folder->setSynchronizedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $emails = $this->manager->getEmails($searchQuery);

        $needFolderFlush = true;
        $count           = 0;
        $batch           = array();
        foreach ($emails as $email) {
            $count++;
            $batch[] = $email;
            if ($count === self::DB_BATCH_SIZE) {
                $this->saveEmails($batch, $imapFolder);
                $needFolderFlush = false;
                $count           = 0;
                $batch           = array();
            }
        }
        if ($count > 0) {
            $this->saveEmails($batch, $imapFolder);
            $needFolderFlush = false;
        }

        if ($needFolderFlush) {
            $this->em->flush();
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

        $folder       = $imapFolder->getFolder();
        $existingUids = $this->getExistingUids($emails, $folder->getId());

        $existingOutdatedEmails = $this->getOutdatedEmails(
            $this->getNewMessageIds($emails, $existingUids),
            $folder->getOrigin()->getId()
        );

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

            /** @var ImapEmail[] $outdatedImapEmails */
            $outdatedImapEmails = $this->findExistingOutdatedEmails($email, $existingOutdatedEmails);
            if (empty($outdatedImapEmails)) {
                $this->log->notice(
                    sprintf('Persisting "%s" email (UID: %d) ...', $email->getSubject(), $email->getId()->getUid())
                );

                $emailEntity = $this->addEmail($email, $folder);
                $uid         = $email->getId()->getUid();
                $imapEmail   = $this->createImapEmail($uid, $emailEntity, $imapFolder);
                $this->em->persist($imapEmail);

                $this->log->notice(sprintf('The "%s" email was persisted.', $email->getSubject()));
            } else {
                $i = 0;
                foreach ($outdatedImapEmails as $outdatedImapEmail) {
                    if ($i === 0) {
                        $outdatedImapEmail->getImapFolder()->setFolder($folder);
                    } else {
                        $this->em->remove($outdatedImapEmail);
                    }
                    $i++;
                }
            }
        }

        $this->emailEntityBuilder->getBatch()->persist($this->em);

        $this->em->flush();
    }

    /**
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
        $emailEntity->addFolder($folder);
        $emailEntity->setMessageId($email->getMessageId());
        $emailEntity->setXMessageId($email->getXMessageId());
        $emailEntity->setXThreadId($email->getXThreadId());

        return $emailEntity;
    }

    /**
     * @param Email[] $emails
     * @param int     $folderId
     *
     * @return int[] array if UIDs
     */
    protected function getExistingUids(array $emails, $folderId)
    {
        $uids = array_map(
            function ($el) {
                /** @var Email $el */
                return $el->getId()->getUid();
            },
            $emails
        );

        $repo = $this->em->getRepository('OroImapBundle:ImapEmail');
        $rows = $repo->createQueryBuilder('e')
            ->select('e.uid')
            ->innerJoin('e.email', 'se')
            ->innerJoin('se.folders', 'sf')
            ->where('sf.id = :folderId AND e.uid IN (:uids)')
            ->setParameter('folderId', $folderId)
            ->setParameter('uids', $uids)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[] = $row['uid'];
        }

        return $result;
    }

    /**
     * @param string[] $messageIds
     * @param int      $originId
     *
     * @return ImapEmail[]
     */
    protected function getOutdatedEmails($messageIds, $originId)
    {
        if (empty($messageIds)) {
            return [];
        }

        $repo = $this->em->getRepository('OroImapBundle:ImapEmail');
        $imapEmails = $repo->createQueryBuilder('e')
            ->select('e, se, f, sf')
            ->innerJoin('e.imapFolder', 'f')
            ->innerJoin('e.email', 'se')
            ->innerJoin('se.folders', 'sf')
            ->innerJoin('sf.origin', 'o')
            ->where('o.id = :originId AND sf.outdatedAt IS NOT NULL AND e.messageId IN (:messageIds)')
            ->setParameter('messageIds', $messageIds)
            ->setParameter('originId', $originId)
            ->getQuery()
            ->getResult();

        return $imapEmails;
    }

    /**
     * @param Email       $email
     * @param ImapEmail[] $existingOutdatedEmails
     *
     * @return ImapEmail[]
     */
    protected function findExistingOutdatedEmails(Email $email, array $existingOutdatedEmails)
    {
        $foundEmails = [];
        foreach ($existingOutdatedEmails as $existingOutdatedEmail) {
            if ($existingOutdatedEmail->getEmail()->getMessageId() === $email->getMessageId()) {
                $foundEmails[$existingOutdatedEmail->getEmail()->getId()][] = $existingOutdatedEmail;
            }
        }

        if (empty($foundEmails)) {
            return [];
        }

        return reset($foundEmails);
    }

    /**
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
     * @param int             $uid
     * @param EmailEntity     $email
     * @param ImapEmailFolder $imapFolder
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
