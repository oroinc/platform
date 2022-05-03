<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentEntityConfigProviderInterface;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validates the compatibility of a field with externally stored file or regular file.
 */
class FileFieldCompatibilityValidator extends ConstraintValidator
{
    private AttachmentEntityConfigProviderInterface $attachmentEntityConfigProvider;

    private FieldHelper $fieldHelper;

    /** @var string[] */
    private array $fieldLabels = [];

    public function __construct(
        AttachmentEntityConfigProviderInterface $attachmentEntityConfigProvider,
        FieldHelper $fieldHelper
    ) {
        $this->attachmentEntityConfigProvider = $attachmentEntityConfigProvider;
        $this->fieldHelper = $fieldHelper;
    }

    /**
     * @param ExternalFile|SymfonyFile|null $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof FileFieldCompatibility) {
            throw new UnexpectedTypeException($constraint, FileFieldCompatibility::class);
        }

        if (null === $value) {
            return;
        }

        $fieldConfig = $this->attachmentEntityConfigProvider->getFieldConfig(
            $constraint->entityClass,
            $constraint->fieldName
        );

        $isStoredExternally = $fieldConfig ? $fieldConfig->get('is_stored_externally') : false;
        $fieldLabel = $this->getFieldLabel($constraint->entityClass, $constraint->fieldName);

        if ($value instanceof ExternalFile) {
            if (!$isStoredExternally) {
                $this->context
                    ->buildViolation($constraint->incompatibleForExternalFileMessage)
                    ->setParameter('{{ filename }}', $this->formatValue($value->getFilename()))
                    ->setParameter('{{ field }}', $fieldLabel)
                    ->setCode(FileFieldCompatibility::INCOMPATIBLE_FIELD_FOR_EXTERNAL_FILE_ERROR)
                    ->addViolation();
            }
        } elseif ($value instanceof SymfonyFile) {
            if ($isStoredExternally) {
                $this->context
                    ->buildViolation($constraint->incompatibleForRegularFileMessage)
                    ->setParameter('{{ filename }}', $this->formatValue($value->getFilename()))
                    ->setParameter('{{ field }}', $fieldLabel)
                    ->setCode(FileFieldCompatibility::INCOMPATIBLE_FIELD_FOR_REGULAR_FILE_ERROR)
                    ->addViolation();
            }
        } else {
            throw new UnexpectedValueException($value, sprintf('%s|%s', SymfonyFile::class, ExternalFile::class));
        }
    }

    private function getFieldLabel(string $className, string $fieldName): ?string
    {
        if (!isset($this->fieldLabels[$className][$fieldName])) {
            $fields = $this->fieldHelper->getRelations($className);

            $this->fieldLabels[$className][$fieldName] = $fields[$fieldName]['label'] ?? $fieldName;
        }

        return $this->fieldLabels[$className][$fieldName];
    }
}
