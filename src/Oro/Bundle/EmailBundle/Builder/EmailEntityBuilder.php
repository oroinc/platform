<?php

namespace Oro\Bundle\EmailBundle\Builder;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Exception\UnexpectedTypeException;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

class EmailEntityBuilder
{
    /**
     * @var EmailEntityBatchProcessor
     */
    private $batch;

    /**
     * @var EmailAddressManager
     */
    private $emailAddressManager;

    /**
     * @var EmailAddressHelper
     */
    private $emailAddressHelper;

    /**
     * Constructor
     *
     * @param EmailEntityBatchProcessor $batch
     * @param EmailAddressManager       $emailAddressManager
     * @param EmailAddressHelper        $emailAddressHelper
     */
    public function __construct(
        EmailEntityBatchProcessor $batch,
        EmailAddressManager $emailAddressManager,
        EmailAddressHelper $emailAddressHelper
    ) {
        $this->batch               = $batch;
        $this->emailAddressManager = $emailAddressManager;
        $this->emailAddressHelper  = $emailAddressHelper;
    }

    /**
     * Create EmailUser entity object
     *
     * @param string               $subject             The email subject
     * @param string $from                              The FROM email address,
     *                                                  for example: john@example.com or "John Smith" <john@example.c4m>
     * @param string|string[]|null $to                  The TO email address(es).
     *                                                  Example of email address see in description of $from parameter
     * @param \DateTime            $sentAt              The date/time when email sent
     * @param \DateTime            $receivedAt          The date/time when email received
     * @param \DateTime            $internalDate        The date/time an email server returned in INTERNALDATE field
     * @param integer $importance                       The email importance flag.
     *                                                  Can be one of *_IMPORTANCE constants of Email class
     * @param string|string[]|null $cc                  The CC email address(es).
     *                                                  Example of email address see in description of $from parameter
     * @param string|string[]|null $bcc                 The BCC email address(es).
     *                                                  Example of email address see in description of $from parameter
     * @param User|Mailbox|null $owner                  Owner of the email
     * @param OrganizationInterface|null $organization
     *
     * @return EmailUser
     *
     * @SuppressWarnings(ExcessiveParameterList)
     */
    public function emailUser(
        $subject,
        $from,
        $to,
        $sentAt,
        $receivedAt,
        $internalDate,
        $importance = Email::NORMAL_IMPORTANCE,
        $cc = null,
        $bcc = null,
        $owner = null,
        $organization = null
    ) {
        $emailUser = new EmailUser();

        $email = $this->email($subject, $from, $to, $sentAt, $internalDate, $importance, $cc, $bcc);
        $emailUser->setReceivedAt($receivedAt);
        $emailUser->setEmail($email);
        if ($owner !== null) {
            if ($owner instanceof User) {
                $emailUser->setOwner($owner);
            } elseif ($owner instanceof Mailbox) {
                $emailUser->setMailboxOwner($owner);
            }
        }
        if ($organization !== null) {
            $emailUser->setOrganization($organization);
        } elseif ($owner !== null) {
            $emailUser->setOrganization($owner->getOrganization());
        }

        $this->batch->addEmailUser($emailUser);

        return $emailUser;
    }

    /**
     * Create Email entity object
     *
     * @param string               $subject      The email subject
     * @param string $from                       The FROM email address,
     *                                           for example: john@example.com or "John Smith" <john@example.c4m>
     * @param string|string[]|null $to           The TO email address(es).
     *                                           Example of email address see in description of $from parameter
     * @param \DateTime            $sentAt       The date/time when email sent
     * @param \DateTime            $internalDate The date/time an email server returned in INTERNALDATE field
     * @param integer $importance                The email importance flag.
     *                                           Can be one of *_IMPORTANCE constants of Email class
     * @param string|string[]|null $cc           The CC email address(es).
     *                                           Example of email address see in description of $from parameter
     * @param string|string[]|null $bcc          The BCC email address(es).
     *                                           Example of email address see in description of $from parameter
     *
     * @return Email
     */
    protected function email(
        $subject,
        $from,
        $to,
        $sentAt,
        $internalDate,
        $importance = Email::NORMAL_IMPORTANCE,
        $cc = null,
        $bcc = null
    ) {
        $result = new Email();
        $result
            ->setSubject($subject)
            ->setFromName($from)
            ->setFromEmailAddress($this->address($from))
            ->setSentAt($sentAt)
            ->setInternalDate($internalDate)
            ->setImportance($importance);

        $this->addRecipients($result, EmailRecipient::TO, $to);
        $this->addRecipients($result, EmailRecipient::CC, $cc);
        $this->addRecipients($result, EmailRecipient::BCC, $bcc);

        return $result;
    }

    /**
     * Add recipients to the specified Email object
     *
     * @param Email  $obj   The Email object recipients is added to
     * @param string $type  The recipient type. Can be to, cc or bcc
     * @param string $email The email address, for example: john@example.com or "John Smith" <john@example.com>
     */
    protected function addRecipients(Email $obj, $type, $email)
    {
        if (!empty($email)) {
            if (is_string($email)) {
                $obj->addRecipient($this->recipient($type, $email));
            } elseif (is_array($email) || $email instanceof \Traversable) {
                foreach ($email as $e) {
                    $obj->addRecipient($this->recipient($type, $e));
                }
            }
        }
    }

    /**
     * Create EmailAddress entity object
     *
     * @param string $email The email address, for example: john@example.com or "John Smith" <john@example.com>
     *
     * @return EmailAddress
     */
    public function address($email)
    {
        $pureEmail = $this->emailAddressHelper->extractPureEmailAddress($email);
        $result    = $this->batch->getAddress($pureEmail);
        if ($result === null) {
            $result = $this->emailAddressManager->newEmailAddress()
                ->setEmail($pureEmail);
            $this->batch->addAddress($result);
        }

        return $result;
    }

    /**
     * Create EmailAttachment entity object
     *
     * @param string $fileName    The attachment file name
     * @param string $contentType The attachment content type. It may be any MIME type
     *
     * @return EmailAttachment
     */
    public function attachment($fileName, $contentType)
    {
        $result = new EmailAttachment();
        $result
            ->setFileName($fileName)
            ->setContentType($contentType);

        return $result;
    }

    /**
     * Create EmailAttachmentContent entity object
     *
     * @param string $content The attachment content encoded
     *                        as it is specified in $contentTransferEncoding parameter
     * @param string $contentTransferEncoding The attachment content encoding type
     *
     * @return EmailAttachmentContent
     */
    public function attachmentContent($content, $contentTransferEncoding)
    {
        $result = new EmailAttachmentContent();
        $result
            ->setContent($content)
            ->setContentTransferEncoding($contentTransferEncoding);

        return $result;
    }

    /**
     * Create EmailBody entity object
     *
     * @param string $content    The body content
     * @param bool   $isHtml     Indicate whether the body content is HTML or TEXT
     * @param bool   $persistent Indicate whether this email body can be removed by the email cache manager or not
     *                           Set false for external email, and true for system email, for example sent by BAP
     *
     * @return EmailBody
     */
    public function body($content, $isHtml, $persistent = false)
    {
        $result = new EmailBody();
        $result
            ->setBodyContent($content)
            ->setBodyIsText(!$isHtml)
            ->setPersistent($persistent);

        return $result;
    }

    /**
     * Create EmailFolder entity object for INBOX folder
     *
     * @param string $fullName The full name of INBOX folder if known
     * @param string $name     The name of INBOX folder if known
     *
     * @return EmailFolder
     */
    public function folderInbox($fullName = null, $name = null)
    {
        return $this->folder(
            FolderType::INBOX,
            $fullName !== null ? $fullName : 'Inbox',
            $name !== null ? $name : 'Inbox'
        );
    }

    /**
     * Create EmailFolder entity object for SENT folder
     *
     * @param string $fullName The full name of SENT folder if known
     * @param string $name     The name of SENT folder if known
     *
     * @return EmailFolder
     */
    public function folderSent($fullName = null, $name = null)
    {
        return $this->folder(
            FolderType::SENT,
            $fullName !== null ? $fullName : 'Sent',
            $name !== null ? $name : 'Sent'
        );
    }

    /**
     * Create EmailFolder entity object for TRASH folder
     *
     * @param string $fullName The full name of TRASH folder if known
     * @param string $name     The name of TRASH folder if known
     *
     * @return EmailFolder
     */
    public function folderTrash($fullName = null, $name = null)
    {
        return $this->folder(
            FolderType::TRASH,
            $fullName !== null ? $fullName : 'Trash',
            $name !== null ? $name : 'Trash'
        );
    }

    /**
     * Create EmailFolder entity object for DRAFTS folder
     *
     * @param string $fullName The full name of DRAFTS folder if known
     * @param string $name     The name of DRAFTS folder if known
     *
     * @return EmailFolder
     */
    public function folderDrafts($fullName = null, $name = null)
    {
        return $this->folder(
            FolderType::DRAFTS,
            $fullName !== null ? $fullName : 'Drafts',
            $name !== null ? $name : 'Drafts'
        );
    }

    /**
     * Create EmailFolder entity object for custom folder
     *
     * @param string $fullName The full name of the folder
     * @param string $name     The name of the folder
     *
     * @return EmailFolder
     */
    public function folderOther($fullName, $name)
    {
        return $this->folder(FolderType::OTHER, $fullName, $name);
    }

    /**
     * Create EmailFolder entity object
     *
     * @param string $type     The folder type. Can be inbox, sent, trash, drafts or other
     * @param string $fullName The full name of a folder
     * @param string $name     The folder name
     *
     * @return EmailFolder
     */
    public function folder($type, $fullName, $name)
    {
        $result = $this->batch->getFolder($type, $fullName);
        if ($result === null) {
            $result = new EmailFolder();
            $result
                ->setType($type)
                ->setFullName($fullName)
                ->setName($name);
            $this->batch->addFolder($result);
        }

        return $result;
    }

    /**
     * Register EmailFolder entity object
     *
     * @param EmailFolder $folder The email folder
     *
     * @return EmailFolder
     */
    public function setFolder(EmailFolder $folder)
    {
        $this->batch->addFolder($folder);

        return $folder;
    }

    /**
     * Create EmailRecipient entity object to store TO field
     *
     * @param string $email The email address, for example: john@example.com or "John Smith" <john@example.com>
     *
     * @return EmailRecipient
     */
    public function recipientTo($email)
    {
        return $this->recipient(EmailRecipient::TO, $email);
    }

    /**
     * Create EmailRecipient entity object to store CC field
     *
     * @param string $email The email address, for example: john@example.com or "John Smith" <john@example.com>
     *
     * @return EmailRecipient
     */
    public function recipientCc($email)
    {
        return $this->recipient(EmailRecipient::CC, $email);
    }

    /**
     * Create EmailRecipient entity object to store BCC field
     *
     * @param string $email The email address, for example: john@example.com or "John Smith" <john@example.com>
     *
     * @return EmailRecipient
     */
    public function recipientBcc($email)
    {
        return $this->recipient(EmailRecipient::BCC, $email);
    }

    /**
     * Create EmailRecipient entity object
     *
     * @param string $type  The recipient type. Can be to, cc or bcc
     * @param string $email The email address, for example: john@example.com or "John Smith" <john@example.com>
     *
     * @return EmailRecipient
     */
    public function recipient($type, $email)
    {
        $result = new EmailRecipient();

        return $result
            ->setType($type)
            ->setName($email)
            ->setEmailAddress($this->address($email));
    }

    /**
     * Set this builder in initial state
     */
    public function clear()
    {
        $this->batch->clear();
    }

    /**
     * Removes all email objects from a batch processor is used this builder
     */
    public function removeEmails()
    {
        $this->batch->removeEmailUsers();
    }

    /**
     * Get built batch contains all entities managed by this builder
     *
     * @return EmailEntityBatchInterface
     */
    public function getBatch()
    {
        return $this->batch;
    }

    /**
     * Tells this builder to manage the given object
     *
     * @param object $obj
     */
    public function setObject($obj)
    {
        if ($obj instanceof EmailUser) {
            $this->batch->addEmailUser($obj);
        } elseif ($obj instanceof EmailAddress) {
            $this->batch->addAddress($obj);
        } elseif ($obj instanceof EmailFolder) {
            $this->batch->addFolder($obj);
        } else {
            throw new UnexpectedTypeException(
                $obj,
                'Oro\Bundle\EmailBundle\Entity\EmailUser'
                . ', Oro\Bundle\EmailBundle\Entity\EmailAddress'
                . ' or Oro\Bundle\EmailBundle\Entity\EmailFolder'
            );
        }
    }
}
