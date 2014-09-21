<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Model\EmailHeader;
use Oro\Bundle\EmailBundle\Model\FolderType;

abstract class AbstractEmailSynchronizationProcessor
{
    /** Determines how many emails can be stored in a database at once */
    const DB_BATCH_SIZE = 30;

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EmailEntityBuilder
     */
    protected $emailEntityBuilder;

    /**
     * @var EmailAddressManager
     */
    protected $emailAddressManager;

    /**
     * Constructor
     *
     * @param LoggerInterface          $log
     * @param EntityManager            $em
     * @param EmailEntityBuilder       $emailEntityBuilder
     * @param EmailAddressManager      $emailAddressManager
     * @param KnownEmailAddressChecker $knownEmailAddressChecker
     */
    protected function __construct(
        LoggerInterface $log,
        EntityManager $em,
        EmailEntityBuilder $emailEntityBuilder,
        EmailAddressManager $emailAddressManager,
        KnownEmailAddressChecker $knownEmailAddressChecker
    ) {
        $this->log                      = $log;
        $this->em                       = $em;
        $this->emailEntityBuilder       = $emailEntityBuilder;
        $this->emailAddressManager      = $emailAddressManager;
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
     * @param EmailHeader $email
     * @param string      $folderType
     *
     * @return bool
     */
    protected function isApplicableEmail(EmailHeader $email, $folderType)
    {
        if ($folderType === FolderType::SENT) {
            return $this->knownEmailAddressChecker->isAtLeastOneKnownEmailAddress(
                $email->getToRecipients(),
                $email->getCcRecipients(),
                $email->getBccRecipients()
            );
        } else {
            return $this->knownEmailAddressChecker->isAtLeastOneKnownEmailAddress(
                $email->getFrom()
            );
        }
    }

    /**
     * @param EmailHeader[] $emails
     * @param string        $folderType
     */
    protected function registerEmailsInKnownEmailAddressChecker(array $emails, $folderType)
    {
        $addresses = [];
        foreach ($emails as $email) {
            if ($folderType === FolderType::SENT) {
                $addresses[] = $email->getToRecipients();
                $addresses[] = $email->getCcRecipients();
                $addresses[] = $email->getBccRecipients();
            } else {
                $addresses[] = $email->getFrom();
            }
        }
        $this->knownEmailAddressChecker->preLoadEmailAddresses($addresses);
    }

    /**
     * Creates email entity and register it in the email entity batch processor
     *
     * @param EmailHeader       $email
     * @param EmailFolder $folder
     *
     * @return EmailEntity
     */
    protected function addEmail(EmailHeader $email, EmailFolder $folder)
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
}
