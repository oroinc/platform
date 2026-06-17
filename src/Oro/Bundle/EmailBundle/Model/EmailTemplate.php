<?php

namespace Oro\Bundle\EmailBundle\Model;

/**
 * Serves to hold and transmit email template information.
 */
class EmailTemplate implements EmailTemplateInterface
{
    public const CONTENT_TYPE_HTML = 'text/html';
    public const CONTENT_TYPE_TEXT = 'text/plain';

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string|null
     */
    private $entityName;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var string
     */
    private $content;

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return $this->type;
    }

    public function getEntityName(): ?string
    {
        return $this->entityName;
    }

    /**
     * @inheritdoc
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @inheritdoc
     */
    public function getContent()
    {
        return $this->content;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $type
     * @return EmailTemplate
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function setEntityName(?string $entityName): self
    {
        $this->entityName = $entityName;

        return $this;
    }

    /**
     * @param string $subject
     * @return EmailTemplate
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @param string $content
     * @return EmailTemplate
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }
}
