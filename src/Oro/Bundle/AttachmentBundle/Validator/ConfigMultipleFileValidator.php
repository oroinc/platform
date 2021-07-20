<?php

namespace Oro\Bundle\AttachmentBundle\Validator;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\MultipleFileConstraintFromEntityFieldConfig;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\MultipleImageConstraintFromEntityFieldConfig;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The validator that can be used to check that a file collection is allowed to be uploaded.
 */
class ConfigMultipleFileValidator
{
    /** @var ValidatorInterface */
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param Collection $value
     * @param string $dataClass
     * @param string $fieldName
     *
     * @return ConstraintViolationListInterface
     */
    public function validateFiles(Collection $value, $dataClass, $fieldName = ''): ConstraintViolationListInterface
    {
        $constraint = new MultipleFileConstraintFromEntityFieldConfig([
            'entityClass' => $dataClass,
            'fieldName' => $fieldName,
        ]);

        return $this->validate(
            $value,
            $constraint
        );
    }

    /**
     * @param Collection $value
     * @param string $dataClass
     * @param string $fieldName
     *
     * @return ConstraintViolationListInterface
     */
    public function validateImages(Collection $value, $dataClass, $fieldName = ''): ConstraintViolationListInterface
    {
        $constraint = new MultipleImageConstraintFromEntityFieldConfig([
            'entityClass' => $dataClass,
            'fieldName' => $fieldName,
        ]);

        return $this->validate(
            $value,
            $constraint
        );
    }

    private function validate(Collection $value, Constraint $constraint): ConstraintViolationListInterface
    {
        return $this->validator->validate(
            $value,
            [$constraint]
        );
    }
}
