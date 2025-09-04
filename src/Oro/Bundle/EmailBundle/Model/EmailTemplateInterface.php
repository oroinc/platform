<?php

namespace Oro\Bundle\EmailBundle\Model;

/**
 * Represents an email message template
 *
 * Will be added in v7.0:
 *
 * @method getAttachments(): iterable<EmailTemplateAttachmentModel>
 */
interface EmailTemplateInterface
{
    public const TYPE_HTML = 'html';
    public const TYPE_TEXT = 'txt';

    public function getName(): ?string;

    public function getType(): ?string;

    public function getSubject(): ?string;

    public function getContent(): ?string;
}
