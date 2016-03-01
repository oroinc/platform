<?php

namespace Oro\Bundle\EmailBundle\Model;

class EmailHeader
{
    /**
     * @var string
     */
    protected $subject;

    /**
     * @var string
     */
    protected $from;

    /**
     * @var string[]
     */
    protected $toRecipients = array();

    /**
     * @var string[]
     */
    protected $ccRecipients = array();

    /**
     * @var string[]
     */
    protected $bccRecipients = array();

    /**
     * @var \DateTime
     */
    protected $receivedAt;

    /**
     * @var \DateTime
     */
    protected $sentAt;

    /**
     * -1 = LOW, 0 = NORMAL, 1 = HIGH
     *
     * @var integer
     */
    protected $importance;

    /**
     * @var \DateTime
     */
    protected $internalDate;

    /**
     * @var string
     */
    protected $messageId;

    /**
     * @var string
     */
    protected $multiMessageId;

    /**
     * @var string
     */
    protected $refs;

    /**
     * @var string
     */
    protected $xMessageId;

    /**
     * @var string
     */
    protected $xThreadId;

    /**
     * @var string
     */
    protected $acceptLanguageHeader;

    /**
     * Get email subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set email subject
     *
     * @param string $subject
     *
     * @return self
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get FROM email
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set FROM email
     *
     * @param string $email
     *
     * @return self
     */
    public function setFrom($email)
    {
        $this->from = $email;

        return $this;
    }

    /**
     * Get email TO recipients
     *
     * @return string[]
     */
    public function getToRecipients()
    {
        return $this->toRecipients;
    }

    /**
     * Add email TO recipient
     *
     * @param string $email
     *
     * @return self
     */
    public function addToRecipient($email)
    {
        $this->toRecipients[] = $email;

        return $this;
    }

    /**
     * Get email CC recipients
     *
     * @return string[]
     */
    public function getCcRecipients()
    {
        return $this->ccRecipients;
    }

    /**
     * Add email CC recipient
     *
     * @param string $email
     *
     * @return self
     */
    public function addCcRecipient($email)
    {
        $this->ccRecipients[] = $email;

        return $this;
    }

    /**
     * Get email BCC recipients
     *
     * @return string[]
     */
    public function getBccRecipients()
    {
        return $this->bccRecipients;
    }

    /**
     * Add email BCC recipient
     *
     * @param string $email
     *
     * @return self
     */
    public function addBccRecipient($email)
    {
        $this->bccRecipients[] = $email;

        return $this;
    }

    /**
     * Get date/time when email received
     *
     * @return \DateTime
     */
    public function getReceivedAt()
    {
        return $this->receivedAt;
    }

    /**
     * Set date/time when email received
     *
     * @param \DateTime $receivedAt
     *
     * @return self
     */
    public function setReceivedAt($receivedAt)
    {
        $this->receivedAt = $receivedAt;

        return $this;
    }

    /**
     * Get date/time when email sent
     *
     * @return \DateTime
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }

    /**
     * Set date/time when email sent
     *
     * @param \DateTime $sentAt
     *
     * @return self
     */
    public function setSentAt($sentAt)
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    /**
     * Get email importance. -1 = LOW, 0 = NORMAL, 1 = HIGH
     *
     * @return integer
     */
    public function getImportance()
    {
        return $this->importance;
    }

    /**
     * Set email importance
     *
     * @param integer -1 = LOW, 0 = NORMAL, 1 = HIGH
     *
     * @return self
     */
    public function setImportance($importance)
    {
        $this->importance = $importance;

        return $this;
    }

    /**
     * Get email internal date receives from an email server
     *
     * @return \DateTime
     */
    public function getInternalDate()
    {
        return $this->internalDate;
    }

    /**
     * Set email internal date receives from an email server
     *
     * @param \DateTime $internalDate
     *
     * @return self
     */
    public function setInternalDate($internalDate)
    {
        $this->internalDate = $internalDate;

        return $this;
    }

    /**
     * Get value of email Message-ID header
     *
     * @return string
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * Set value of email Message-ID header
     *
     * @param string $messageId
     *
     * @return self
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;

        return $this;
    }

    /**
     * Get value of email References header
     *
     * @return string
     */
    public function getRefs()
    {
        return $this->refs;
    }

    /**
     * Set value of email References header
     *
     * @param string $references
     *
     * @return self
     */
    public function setRefs($references)
    {
        $this->refs = $references;

        return $this;
    }

    /**
     * Get email message id uses for group related messages
     *
     * @return string
     */
    public function getXMessageId()
    {
        return $this->xMessageId;
    }

    /**
     * Set email message id uses for group related messages
     *
     * @param string $xMessageId
     *
     * @return self
     */
    public function setXMessageId($xMessageId)
    {
        $this->xMessageId = $xMessageId;

        return $this;
    }

    /**
     * Get email thread id uses for group related messages
     *
     * @return string
     */
    public function getXThreadId()
    {
        return $this->xThreadId;
    }

    /**
     * Set email thread id uses for group related messages
     *
     * @param $xThreadId
     *
     * @return self
     */
    public function setXThreadId($xThreadId)
    {
        $this->xThreadId = $xThreadId;

        return $this;
    }

    /**
     * Get array values of email Message-ID header
     *
     * @return array|null
     */
    public function getMultiMessageId()
    {
        return $this->multiMessageId ? unserialize($this->multiMessageId) : null;
    }

    /**
     * Set array values of email Message-ID header
     *
     * @param array|null $multiMessageId - array of message id
     *
     * @return self
     */
    public function setMultiMessageId($multiMessageId)
    {
        $this->multiMessageId = $multiMessageId ? serialize($multiMessageId): null;

        return $this;
    }

    /**
     * @return string
     */
    public function getAcceptLanguageHeader()
    {
        return $this->acceptLanguageHeader;
    }

    /**
     * @param string $acceptLanguageHeader
     */
    public function setAcceptLanguageHeader($acceptLanguageHeader)
    {
        $this->acceptLanguageHeader = $acceptLanguageHeader;
    }
}
