<?php

namespace Oro\Bundle\EmailBundle\Form\Model;

use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailThread;

class EmailApi
{
    /** @var EmailEntity|null */
    protected $entity;

    /** @var array */
    protected $folders = [];

    /** @var string */
    protected $from;

    /** @var string[] */
    protected $to = [];

    /** @var string[] */
    protected $cc = [];

    /** @var string[] */
    protected $bcc = [];

    /** @var string */
    protected $subject;

    /** @var string */
    protected $body;

    /** @var bool */
    protected $bodyType;

    /** @var \DateTime */
    protected $createdAt;

    /** @var \DateTime */
    protected $sentAt;

    /** @var \DateTime */
    protected $receivedAt;

    /** @var \DateTime */
    protected $internalDate;

    /** @var int */
    protected $importance;

    /** @var bool */
    protected $head;

    /** @var bool */
    protected $seen;

    /** @var string */
    protected $messageId;

    /** @var string */
    protected $xMessageId;

    /** @var string */
    protected $xThreadId;

    /** @var int */
    protected $thread;

    /** @var string */
    protected $refs;

    /**
     * @param EmailEntity|null $entity
     */
    public function __construct(EmailEntity $entity = null)
    {
        $this->entity = $entity;
    }

    /**
     * Get the email entity linked to this model
     *
     * @return EmailEntity|null
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set the email entity linked to this model
     *
     * @param EmailEntity $entity
     *
     * @return self
     */
    public function setEntity(EmailEntity $entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get email folders
     *
     * @return array
     */
    public function getFolders()
    {
        return $this->folders;
    }

    /**
     * Set email folders
     *
     * @param array $folders
     *
     * @return self
     */
    public function setFolders($folders)
    {
        $this->folders = $folders;

        return $this;
    }

    /**
     * Get FROM email address
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set FROM email address
     *
     * @param string $from
     *
     * @return self
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Get TO email addresses
     *
     * @return string[]
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Set TO email addresses
     *
     * @param string[] $to
     *
     * @return self
     */
    public function setTo(array $to)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * Get CC email addresses
     *
     * @return string[]
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * Set CC email addresses
     *
     * @param string[] $cc
     *
     * @return self
     */
    public function setCc(array $cc)
    {
        $this->cc = $cc;

        return $this;
    }

    /**
     * Get BCC email addresses
     *
     * @return string[]
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * Set BCC email addresses
     *
     * @param string[] $bcc
     *
     * @return self
     */
    public function setBcc(array $bcc)
    {
        $this->bcc = $bcc;

        return $this;
    }

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
     * Get email body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set email body
     *
     * @param string $body
     *
     * @return self
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get email body type
     *
     * @return bool Can be true for 'text' or false for 'html'
     */
    public function getBodyType()
    {
        return $this->bodyType;
    }

    /**
     * Set email body type
     *
     * @param bool $bodyType Can be true for 'text' or false for 'html'
     *
     * @return self
     */
    public function setBodyType($bodyType)
    {
        $this->bodyType = $bodyType;

        return $this;
    }

    /**
     * Get email creation date
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set email creation date
     *
     * @param \DateTime $createdAt
     *
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get email sent date
     *
     * @return \DateTime
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }

    /**
     * Set email sent date
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
     * Get email received date
     *
     * @return \DateTime
     */
    public function getReceivedAt()
    {
        return $this->receivedAt;
    }

    /**
     * Set email received date
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
     * Get email internal date
     *
     * @return \DateTime
     */
    public function getInternalDate()
    {
        return $this->internalDate;
    }

    /**
     * Set email internal date
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
     * Get email body type
     *
     * @return int Can be any of Email::*_IMPORTANCE
     */
    public function getImportance()
    {
        return $this->importance;
    }

    /**
     * Set email body type
     *
     * @param int $importance Can be any of Email::*_IMPORTANCE
     *
     * @return self
     */
    public function setImportance($importance)
    {
        $this->importance = $importance;

        return $this;
    }

    /**
     * Indicate if email is either first unread, or the last item in the thread
     *
     * @return bool
     */
    public function isHead()
    {
        return $this->head;
    }

    /**
     * Set a flag indicates if email is either first unread, or the last item in the thread
     *
     * @param string $head
     *
     * @return self
     */
    public function setHead($head)
    {
        $this->head = (bool)$head;

        return $this;
    }

    /**
     * Indicate if email is seen
     *
     * @return string
     */
    public function isSeen()
    {
        return $this->seen;
    }

    /**
     * Set a flag indicates whether email is seen
     *
     * @param string $seen
     *
     * @return self
     */
    public function setSeen($seen)
    {
        $this->seen = (bool)$seen;

        return $this;
    }

    /**
     * Get email Message-ID
     *
     * @return string
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * Set email Message-ID
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
     * Get email message id uses for group related messages
     *
     * @return string
     */
    public function getXMessageId()
    {
        return $this->xMessageId;
    }

    /**
     * Get email message id uses for group related messages
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
     * @param string $xThreadId
     *
     * @return Email
     */
    public function setXThreadId($xThreadId)
    {
        $this->xThreadId = $xThreadId;

        return $this;
    }

    /**
     * Get thread
     *
     * @return EmailThread
     */
    public function getThread()
    {
        return $this->thread;
    }

    /**
     * Set thread
     *
     * @param EmailThread|null $thread
     *
     * @return Email
     */
    public function setThread($thread)
    {
        $this->thread = $thread;

        return $this;
    }

    /**
     * Get email references
     *
     * @return string
     */
    public function getRefs()
    {
        return $this->refs;
    }

    /**
     * Set email references
     *
     * @param string $refs
     *
     * @return $this
     */
    public function setRefs($refs)
    {
        $this->refs = $refs;

        return $this;
    }
}
