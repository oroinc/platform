<?php

namespace Oro\Bundle\EmailBundle\Model;

/**
 * Represents an email message template
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
