<?php

namespace Oro\Bundle\EmailBundle\Form\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

/**
 * Class Email
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 *
 * @package Oro\Bundle\EmailBundle\Form\Model
 */
class Email implements OrganizationAwareInterface
{
    const MAIL_TYPE_DIRECT  = 'direct';
    const MAIL_TYPE_REPLY   = 'reply';
    const MAIL_TYPE_FORWARD = 'forward';

    /** @var string */
    protected $gridName;

    /** @var string */
    protected $entityClass;

    /** @var mixed */
    protected $entityId;

    /** @var int */
    protected $parentEmailId;

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

    /** @var EmailTemplate */
    protected $template;

    /** @var string text or html */
    protected $type;

    /** @var string */
    protected $body;

    /** @var string */
    protected $signature;

    /** @var string */
    protected $bodyFooter = '';

    /** @var object[] */
    protected $contexts = [];

    /** @var Collection */
    protected $attachments;

    /** @var  string */
    protected $mailType;

    /** @var array */
    protected $attachmentsAvailable;

    /** @var  Organization */
    protected $organization;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->attachments = new ArrayCollection();
    }

    /**
     * Get id of emails datagrid
     *
     * @return string
     */
    public function getGridName()
    {
        return $this->gridName;
    }

    /**
     * Set id of emails datagrid
     *
     * @param string $gridName
     *
     * @return $this
     */
    public function setGridName($gridName)
    {
        $this->gridName = $gridName;

        return $this;
    }

    /**
     * Get class name of the target entity
     *
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * Set class name of the target entity
     *
     * @param string $entityClass
     *
     * @return $this
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * Get id of the target entity
     *
     * @return string
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * Set id of the target entity
     *
     * @param string $entityId
     *
     * @return $this
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * Get parent email id
     *
     * @return int
     */
    public function getParentEmailId()
    {
        return $this->parentEmailId;
    }

    /**
     * Set parent email id
     *
     * @param $parentEmailId
     *
     * @return $this
     */
    public function setParentEmailId($parentEmailId)
    {
        $this->parentEmailId = $parentEmailId;

        return $this;
    }

    /**
     * Indicates whether entity class and entity id is set
     *
     * @return bool
     */
    public function hasEntity()
    {
        return !empty($this->entityClass) && !empty($this->entityId);
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @param EmailTemplate $template
     *
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @return EmailTemplate
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
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
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get email body footer
     *
     * @return string
     */
    public function getBodyFooter()
    {
        return $this->bodyFooter;
    }

    /**
     * Set email body footer
     *
     * @param string  $bodyFooter
     *
     * @return Email
     */
    public function setBodyFooter($bodyFooter)
    {
        $this->bodyFooter = $bodyFooter;

        return $this;
    }

    /**
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * @param string $signature
     *
     * @return $this
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @param EmailAttachment $attachment
     */
    public function addAttachment(EmailAttachment $attachment)
    {
        $this->attachments->add($attachment);
    }

    /**
     * @param EmailAttachment $attachment
     */
    public function removeAttachment(EmailAttachment $attachment)
    {
        if ($this->attachments->contains($attachment)) {
            $this->attachments->remove($attachment);
        }
    }

    /**
     * @param EmailAttachment[] $attachments
     *
     * @return $this
     */
    public function setAttachments(array $attachments)
    {
        $this->attachments = $attachments;

        return $this;
    }

    /**
     * @return EmailAttachment[]
     */
    public function getAttachmentsAvailable()
    {
        return $this->attachmentsAvailable;
    }

    /**
     * @param EmailAttachment[] $attachmentsAvailable
     *
     * @return $this
     */
    public function setAttachmentsAvailable($attachmentsAvailable)
    {
        $this->attachmentsAvailable = $attachmentsAvailable;

        return $this;
    }

    /**
     * @return string
     */
    public function getMailType()
    {
        return $this->mailType;
    }

    /**
     * @param string $mailType
     *
     * @return $this
     */
    public function setMailType($mailType)
    {
        $this->mailType = $mailType;

        return $this;
    }

    /**
     * Get contexts
     *
     * @return object[]
     */
    public function getContexts()
    {
        return $this->contexts;
    }

    /**
     * Set contexts
     *
     * @param object[] $contexts
     *
     * @return $this
     */
    public function setContexts(array $contexts)
    {
        $this->contexts = $contexts;

        return $this;
    }

    /**
     * Get organization
     *
     * @return OrganizationInterface
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set organization
     *
     * @param OrganizationInterface $organization
     * @return $this
     */
    public function setOrganization(OrganizationInterface $organization)
    {
        $this->organization = $organization;

        return $this;
    }
}
