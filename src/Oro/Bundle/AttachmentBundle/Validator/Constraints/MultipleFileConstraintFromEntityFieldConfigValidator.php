<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AttachmentBundle\Provider\MultipleFileConstraintsProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * - fetches max number of files for multiFile from entity field config
 */
class MultipleFileConstraintFromEntityFieldConfigValidator extends ConstraintValidator
{
    /** @var MultipleFileConstraintsProvider */
    private $constraintsProvider;

    public function __construct(MultipleFileConstraintsProvider $multipleFileConstraintsProvider)
    {
        $this->constraintsProvider = $multipleFileConstraintsProvider;
    }

    /**
     * @var Collection $value
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof MultipleFileConstraintFromEntityFieldConfig) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected instance of %s, got %s',
                    MultipleFileConstraintFromEntityFieldConfig::class,
                    get_class($constraint)
                )
            );
        }
        if (!$value instanceof Collection) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected instance of %s, got %s',
                    Collection::class,
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        $dataClass = $constraint->getEntityClass();
        $fieldName = $constraint->getFieldName();

        if ($fieldName === '') {
            $maxNumberOfFiles = $this->constraintsProvider->getMaxNumberOfFilesForEntity($dataClass);
        } else {
            $maxNumberOfFiles = $this->constraintsProvider->getMaxNumberOfFilesForEntityField($dataClass, $fieldName);
        }

        if (0 != $maxNumberOfFiles && $value->count() > $maxNumberOfFiles) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameters(['{{max}}' => $maxNumberOfFiles])
                ->setPlural($maxNumberOfFiles)
                ->addViolation();
        }
    }
}
