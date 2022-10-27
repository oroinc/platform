<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for the URL of {@see ExternalFile}.
 */
class ExternalFileUrl extends Constraint
{
    public const INVALID_URL_REGEXP_ERROR = '5aae04b8-087c-40a4-a07c-866ae472e2b0';
    public const EMPTY_REGEXP_ERROR = '183541f9-810c-4eb4-9f44-21f86a332c9a';
    public const INVALID_REGEXP_ERROR = '3d1cc7ef-4304-4700-970d-d72f6e721baa';

    protected static $errorNames = [
        self::INVALID_URL_REGEXP_ERROR => 'INVALID_URL_REGEXP_ERROR',
        self::EMPTY_REGEXP_ERROR => 'EMPTY_REGEXP_ERROR',
        self::INVALID_REGEXP_ERROR => 'INVALID_REGEXP_ERROR',
    ];

    public string $allowedUrlsRegExp = '';

    public string $doesNoMatchRegExpMessage = 'oro.attachment.external_file.does_not_match_regexp';

    public string $invalidRegExpMessage = 'oro.attachment.external_file.invalid_regexp';

    public string $emptyRegExpMessage = '';

    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }
}
