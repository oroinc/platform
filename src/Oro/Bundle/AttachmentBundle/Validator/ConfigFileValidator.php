<?php

namespace Oro\Bundle\AttachmentBundle\Validator;

use Oro\Bundle\AttachmentBundle\Provider\FileConstraintsProvider;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The validator that can be used to check that a file is allowed to be uploaded.
 */
class ConfigFileValidator
{
    /** @var ValidatorInterface */
    private $validator;

    /** @var FileConstraintsProvider */
    private $fileConstraintsProvider;

    public function __construct(ValidatorInterface $validator, FileConstraintsProvider $fileConstraintsProvider)
    {
        $this->validator = $validator;
        $this->fileConstraintsProvider = $fileConstraintsProvider;
    }

    /**
     * @param ComponentFile $file      A file object to be validated
     * @param string        $dataClass The FQCN of a parent entity
     * @param string        $fieldName The name of file/image field
     *
     * @return ConstraintViolationListInterface
     */
    public function validate($file, $dataClass, $fieldName = ''): ConstraintViolationListInterface
    {
        if ($fieldName === '') {
            $mimeTypes = $this->fileConstraintsProvider->getAllowedMimeTypesForEntity($dataClass);
            $maxFileSize = $this->fileConstraintsProvider->getMaxSizeForEntity($dataClass);
        } else {
            $mimeTypes = $this->fileConstraintsProvider->getAllowedMimeTypesForEntityField($dataClass, $fieldName);
            $maxFileSize = $this->fileConstraintsProvider->getMaxSizeForEntityField($dataClass, $fieldName);
        }

        return $this->validator->validate(
            $file,
            [new FileConstraint(['maxSize' => $maxFileSize, 'mimeTypes' => $mimeTypes])]
        );
    }
}
