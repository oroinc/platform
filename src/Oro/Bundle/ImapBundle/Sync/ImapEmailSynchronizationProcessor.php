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

        $uids = array_map(
            function ($el) {
                /** @var Email $el */
                return $el->getId()->getUid();
            },
            $emails
        );

        $folder       = $imapFolder->getFolder();
        $repo         = $this->em->getRepository('OroImapBundle:ImapEmail');
        $imapDataRows = $repo->createQueryBuilder('e')
            ->select('e.uid, se.id')
            ->innerJoin('e.email', 'se')
            ->innerJoin('se.folders', 'sf')
            ->where('sf.id = :folderId AND e.uid IN (:uids)')
            ->setParameter('folderId', $folder->getId())
            ->setParameter('uids', $uids)
            ->getQuery()
            ->getResult();

        $existingUids = array_map(
            function ($el) {
                return $el['uid'];
            },
            $imapDataRows
        );

        $existingEmailIds = array_map(
            function ($el) {
                return $el['id'];
            },
            $imapDataRows
        );

        $newImapIds = [];
        foreach ($emails as $src) {
            if (!in_array($src->getId()->getUid(), $existingUids)) {
                $this->log->notice(
                    sprintf('Persisting "%s" email (UID: %d) ...', $src->getSubject(), $src->getId()->getUid())
                );

                $email = $this->emailEntityBuilder->email(
                    $src->getSubject(),
                    $src->getFrom(),
                    $src->getToRecipients(),
                    $src->getSentAt(),
                    $src->getReceivedAt(),
                    $src->getInternalDate(),
                    $src->getImportance(),
                    $src->getCcRecipients(),
                    $src->getBccRecipients()
                );
                $email->addFolder($folder);
                $email->setMessageId($src->getMessageId());
                $email->setXMessageId($src->getXMessageId());
                $email->setXThreadId($src->getXThreadId());

                if (!isset($newImapIds[$src->getMessageId()])) {
                    $newImapIds[$src->getMessageId()] = [];
                }
                $uid                                    = $src->getId()->getUid();
                $newImapIds[$src->getMessageId()][$uid] = $uid;

                $this->log->notice(sprintf('The "%s" email was persisted.', $src->getSubject()));
            } else {
                $this->log->notice(
                    sprintf(
                        'Skip "%s" (UID: %d) email, because it is already synchronised.',
                        $src->getSubject(),
                        $src->getId()->getUid()
                    )
                );
            }
        }

        $this->emailEntityBuilder->getBatch()->persist($this->em);
        $this->linkEmailsToImapEmails($emails, $newImapIds, $existingEmailIds, $imapFolder);
        $this->em->flush();
    }

    /**
     * @param Email[]|array   $emails
     * @param array           $newImapIds
     * @param array           $existingEmailIds
     * @param ImapEmailFolder $imapFolder
     */
    protected function linkEmailsToImapEmails(
        array $emails,
        array $newImapIds,
        array $existingEmailIds,
        ImapEmailFolder $imapFolder
    ) {
        /** @var EmailEntity[] $oEmails */
        $oEmails = $this->getEmailsByMessageId(
            $this->emailEntityBuilder->getBatch()->getEmails()
        );

        foreach ($emails as $emailDTO) {
            if (empty($newImapIds[$emailDTO->getMessageId()])) {
                // email was skipped
                continue;
            }

            /** @var EmailEntity $email */
            $email = $oEmails[$emailDTO->getMessageId()];
            if (in_array($email->getId(), $existingEmailIds)) {
                continue;
            }

            /** @var int[] $newImapIdArray */
            $newImapIdArray = $newImapIds[$emailDTO->getMessageId()];

            foreach ($newImapIdArray as $newImapId) {
                $imapEmail = new ImapEmail();
                $imapEmail
                    ->setUid($newImapId)
                    ->setEmail($email)
                    ->setImapFolder($imapFolder);

                $this->em->persist($imapEmail);
            }
        }
    }
}
