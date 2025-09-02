<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating that only one field of {@see EmailTemplateAttachment} is not blank.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class EmailTemplateAttachmentNotBlank extends Constraint
{
    public const string ONLY_ONE_FIELD_NOT_BLANK = '546c9f1c-6571-48fb-a98f-dd1d231edc93';

    protected const ERROR_NAMES = [
        self::ONLY_ONE_FIELD_NOT_BLANK => 'ONLY_ONE_FIELD_NOT_BLANK',
    ];

    public string $message = 'oro.email.validator.email_template_attachment.one_field_not_blank';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
