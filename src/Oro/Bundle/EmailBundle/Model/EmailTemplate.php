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
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var string
     */
    private $content;

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return $this->type;
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

    /**
     * @param string $type
     * @return EmailTemplate
     */
    public function setType($type)
    {
        $this->type = $type;

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
