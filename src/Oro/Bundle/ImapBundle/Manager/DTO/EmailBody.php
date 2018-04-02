<?php

namespace Oro\Bundle\ImapBundle\Manager\DTO;

use Zend\Mail\Header\ContentType;

/**
 * Represents IMAP email body.
 */
class EmailBody
{
    /**
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $originalContentType;

    /**
     * @var bool
     */
    protected $bodyIsText;

    /**
     * Get body content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set body content.
     *
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalContentType()
    {
        return $this->originalContentType;
    }

    /**
     * @param string|ContentType $originalContentType
     * @return $this
     */
    public function setOriginalContentType($originalContentType)
    {
        if ($originalContentType instanceof ContentType) {
            $originalContentType = $originalContentType->getType();
        }

        $this->originalContentType = strtolower($originalContentType);

        return $this;
    }

    /**
     * Indicate whether email body is a text or html.
     *
     * @return bool true if body is text/plain; otherwise, the body content is text/html
     */
    public function getBodyIsText()
    {
        return $this->bodyIsText;
    }

    /**
     * Set body content type.
     *
     * @param bool $bodyIsText true for text/plain, false for text/html
     * @return $this
     */
    public function setBodyIsText($bodyIsText)
    {
        $this->bodyIsText = $bodyIsText;

        return $this;
    }
}
