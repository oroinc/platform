<?php

namespace Oro\Bundle\AttachmentBundle\Validator;

use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Provider\FileConstraintsProvider;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\ExternalFileMimeType;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\ExternalFileUrl;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileFieldCompatibility;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
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
     * @param string $dataClass The FQCN of a parent entity
     * @param string $fieldName The name of file/image field
     *
     * @return ConstraintViolationListInterface
     */
    public function validate($file, $dataClass, $fieldName = ''): ConstraintViolationListInterface
    {
        if ($file === null) {
            return new ConstraintViolationList();
        }

        $constraints = [];

        if ($fieldName) {
            $constraints[] = new FileFieldCompatibility(['entityClass' => $dataClass, 'fieldName' => $fieldName]);
        }

        if ($file instanceof SymfonyFile) {
            $constraints[] = new SymfonyFileConstraint(
                [
                    'maxSize' => $this->getMaxFileSize($dataClass, $fieldName),
                    'mimeTypes' => $this->getMimeTypes($dataClass, $fieldName),
                    'mimeTypesMessage' => 'oro.attachment.mimetypes.invalid_mime_type',
                ]
            );
        } elseif ($file instanceof ExternalFile) {
            $constraints[] = $this->createExternalFileUrlConstraint();
            $constraints[] = new ExternalFileMimeType(['mimeTypes' => $this->getMimeTypes($dataClass, $fieldName)]);
        } else {
            throw new \InvalidArgumentException(
                sprintf('Argument of type "%s" is not supported', get_debug_type($file))
            );
        }

        return $this->validator->validate($file, [new Sequentially(['constraints' => $constraints])]);
    }

    private function getMaxFileSize(string $dataClass, string $fieldName = ''): int
    {
        return $fieldName === ''
            ? $this->fileConstraintsProvider->getMaxSizeForEntity($dataClass)
            : $this->fileConstraintsProvider->getMaxSizeForEntityField($dataClass, $fieldName);
    }

    private function getMimeTypes(string $dataClass, string $fieldName = ''): array
    {
        return $fieldName === ''
            ? $this->fileConstraintsProvider->getAllowedMimeTypesForEntity($dataClass)
            : $this->fileConstraintsProvider->getAllowedMimeTypesForEntityField($dataClass, $fieldName);
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
