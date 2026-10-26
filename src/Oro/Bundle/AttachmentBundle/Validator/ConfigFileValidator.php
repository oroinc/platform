<?php

namespace Oro\Bundle\AttachmentBundle\Validator;

use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Model\FileValidationTarget;
use Oro\Bundle\AttachmentBundle\Provider\FileConstraintsProvider;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\ExternalFileMimeType;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\ExternalFileUrl;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileFieldCompatibility;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\File as SymfonyFileConstraint;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The validator that can be used to check that a file is allowed to be uploaded.
 */
class ConfigFileValidator
{
    private ValidatorInterface $validator;

    private FileConstraintsProvider $fileConstraintsProvider;

    public function __construct(ValidatorInterface $validator, FileConstraintsProvider $fileConstraintsProvider)
    {
        $this->validator = $validator;
        $this->fileConstraintsProvider = $fileConstraintsProvider;
    }

    /**
     * @param \SplFileInfo $file A file object to be validated
     * @param string $dataClass The FQCN of a parent entity, or an empty string to apply the system-wide restrictions
     * @param string $fieldName The name of file/image field
     *
     * @return ConstraintViolationListInterface
     */
    public function validate($file, $dataClass, $fieldName = ''): ConstraintViolationListInterface
    {
        if ($file === null) {
            return new ConstraintViolationList();
        }

        return $this->validator->validate(
            $file,
            $this->getConstraints($file, new FileValidationTarget($dataClass, $fieldName))
        );
    }

    /**
     * @return Constraint[]
     *
     * @throws \InvalidArgumentException if the file type is not supported
     */
    public function getConstraints(object $file, FileValidationTarget $target): array
    {
        $constraints = [];

        if ('' !== $target->fieldName) {
            $constraints[] = new FileFieldCompatibility(
                ['entityClass' => $target->entityClass, 'fieldName' => $target->fieldName]
            );
        }

        if ($file instanceof SymfonyFile) {
            $constraints[] = new SymfonyFileConstraint(
                [
                    'maxSize' => $this->getMaxFileSize($target),
                    'mimeTypes' => $this->getMimeTypes($target),
                    'mimeTypesMessage' => 'oro.attachment.mimetypes.invalid_mime_type',
                ]
            );
        } elseif ($file instanceof ExternalFile) {
            $constraints[] = $this->createExternalFileUrlConstraint();
            $constraints[] = new ExternalFileMimeType(['mimeTypes' => $this->getMimeTypes($target)]);
        } else {
            throw new \InvalidArgumentException(
                sprintf('Argument of type "%s" is not supported', get_debug_type($file))
            );
        }

        return [new Sequentially(['constraints' => $constraints])];
    }

    private function getMaxFileSize(FileValidationTarget $target): int
    {
        if ($target->isSystem()) {
            return $this->fileConstraintsProvider->getMaxSize();
        }

        return '' === $target->fieldName
            ? $this->fileConstraintsProvider->getMaxSizeForEntity($target->entityClass)
            : $this->fileConstraintsProvider->getMaxSizeForEntityField($target->entityClass, $target->fieldName);
    }

    private function getMimeTypes(FileValidationTarget $target): array
    {
        if ($target->isSystem()) {
            return $this->fileConstraintsProvider->getMimeTypes();
        }

        return '' === $target->fieldName
            ? $this->fileConstraintsProvider->getAllowedMimeTypesForEntity($target->entityClass)
            : $this->fileConstraintsProvider->getAllowedMimeTypesForEntityField(
                $target->entityClass,
                $target->fieldName
            );
    }

    public function validateExternalFileUrl(string $url): ConstraintViolationListInterface
    {
        return $this->validator->validate($url, $this->createExternalFileUrlConstraint());
    }

    private function createExternalFileUrlConstraint(): ExternalFileUrl
    {
        return new ExternalFileUrl(
            [
                'allowedUrlsRegExp' => $this->fileConstraintsProvider->getExternalFileAllowedUrlsRegExp(),
                'emptyRegExpMessage' => 'oro.attachment.external_file.empty_regexp',
            ]
        );
    }
}
