<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Model\EmailHeader;
use Oro\Bundle\EmailBundle\Model\FolderType;

abstract class AbstractEmailSynchronizationProcessor implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** Determines how many emails can be stored in a database at once */
    const DB_BATCH_SIZE = 30;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EmailEntityBuilder
     */
    protected $emailEntityBuilder;

    /**
     * @var array
     */
    private $emailOriginUsers = [];

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
     * Returns the id of a user the given email origin belongs to.
     *
     * @param EmailOrigin $origin
     *
     * @return int|null
     */
    protected function getUserId(EmailOrigin $origin)
    {
        if (isset($this->emailOriginUsers[$origin->getId()])
            || array_key_exists($origin->getId(), $this->emailOriginUsers)
        ) {
            return $this->emailOriginUsers[$origin->getId()];
        }

        $this->logger->notice(sprintf('Finding an user for email origin "%s" ...', (string)$origin));
        $qb = $this->em->getRepository('Oro\Bundle\UserBundle\Entity\User')
            ->createQueryBuilder('u')
            ->select('u.id')
            ->innerJoin('u.emailOrigins', 'o')
            ->where('o.id = :originId')
            ->setParameter('originId', $origin->getId())
            ->setMaxResults(1);

        $result = $qb->getQuery()->getArrayResult();
        $userId = !empty($result) ? $result[0]['id'] : null;
        if ($userId === null) {
            $this->logger->notice('The user was not found.');
        } else {
            $this->logger->notice(sprintf('The user id: %s.', $userId));
        }
        $this->emailOriginUsers[$origin->getId()] = $userId;

        return $userId;
    }

    /**
     * @param EmailHeader $email
     * @param string      $folderType
     * @param int|null    $userId
     *
     * @return bool
     */
    protected function isApplicableEmail(EmailHeader $email, $folderType, $userId = null)
    {
        if ($userId === null) {
            return $this->isKnownSender($email) && $this->isKnownRecipient($email);
        }

        if ($folderType === FolderType::SENT) {
            return $this->isUserSender($userId, $email) && $this->isKnownRecipient($email);
        } else {
            return $this->isKnownSender($email) && $this->isUserRecipient($userId, $email);
        }
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
     * @param EmailHeader $email
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

        return ($folderType1 === $folderType2);
    }
}
