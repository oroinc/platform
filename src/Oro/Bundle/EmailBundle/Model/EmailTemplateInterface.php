<?php

namespace Oro\Bundle\EmailBundle\Model;

/**
 * Represents an email message template
 */
interface EmailTemplateInterface
{
    /**
     * Gets email template type
     *
     * @return string
     */
    public function getType();

    /**
     * Gets email subject
     *
     * @return string
     */
    public function getSubject();

    /**
     * Gets email template content
     *
     * @return string
     */
    public function getContent();

    /**
     * Sets email template content
     *
     * @param string $content
     *
     * @return EmailTemplateInterface
     */
    public function setContent($content);

    /**
     * Sets email subject
     *
     * @param string $subject
     *
     * @return EmailTemplateInterface
     */
    public function setSubject($subject);
}
