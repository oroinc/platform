<?php

namespace Oro\Bundle\EmailBundle\Form\Model;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;

class Email implements EmailTemplateAwareInterface
{
    /** @var string */
    protected $gridName;

    /** @var string */
    protected $entityName;

    /** @var mixed */
    protected $entityId;

    /** @var string */
    protected $from;

    /** @var string[] */
    protected $to;

    /** @var string */
    protected $subject;

    /** @var EmailTemplate */
    protected $template;

    /** @var string */
    protected $body;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->to = array();
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
     * {@inheritdoc}
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * Set class name of the target entity
     *
     * @param string $entityName
     *
     * @return $this
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;

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
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
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
}
