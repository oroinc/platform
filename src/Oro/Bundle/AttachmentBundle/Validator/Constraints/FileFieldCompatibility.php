<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for checking compatibility of an externally stored file or regular file with an entity field.
 */
class FileFieldCompatibility extends Constraint
{
    public const INCOMPATIBLE_FIELD_FOR_EXTERNAL_FILE_ERROR = '2ddaebf5-1dc4-4d69-9baf-fa1fb69abff4';
    public const INCOMPATIBLE_FIELD_FOR_REGULAR_FILE_ERROR = '1fc38319-18e4-45e5-aa7c-919ea8bd1234';

    protected static $errorNames = [
        self::INCOMPATIBLE_FIELD_FOR_EXTERNAL_FILE_ERROR => 'INCOMPATIBLE_FIELD_FOR_EXTERNAL_FILE_ERROR',
        self::INCOMPATIBLE_FIELD_FOR_REGULAR_FILE_ERROR => 'INCOMPATIBLE_FIELD_FOR_REGULAR_FILE_ERROR',
    ];

    public string $entityClass = '';
    public string $fieldName = '';

    public string $incompatibleForExternalFileMessage = 'oro.attachment.incompatible_field.external_file';
    public string $incompatibleForRegularFileMessage = 'oro.attachment.incompatible_field.regular_file';

    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT];
    }

    public function getRequiredOptions(): array
    {
        return ['entityClass', 'fieldName'];
    }
}
