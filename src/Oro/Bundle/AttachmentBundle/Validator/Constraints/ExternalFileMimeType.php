<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for the mime type of {@see ExternalFile}.
 */
class ExternalFileMimeType extends Constraint
{
    public const INVALID_MIME_TYPE_ERROR = '349af3a8-9059-416c-bb74-9f774f357714';

    protected static $errorNames = [
        self::INVALID_MIME_TYPE_ERROR => 'INVALID_MIME_TYPE_ERROR',
    ];

    public array $mimeTypes = [];

    public string $message = 'oro.attachment.external_file.invalid_mime_type';

    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }
}
