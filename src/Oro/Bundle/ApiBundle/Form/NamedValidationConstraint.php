<?php

namespace Oro\Bundle\ApiBundle\Form;

use Symfony\Component\Validator\Constraint;

/**
 * The validation constraint that is used to add named validation errors
 * without having own constraint class for each type of an error.
 * @internal Do not create an instance of this class directly, use FormUtil::addNamedFormError instead
 * @see      \Oro\Bundle\ApiBundle\Form\FormUtil::addNamedFormError
 */
class NamedValidationConstraint extends Constraint
{
    /** @var string */
    private $constraintType;

    /**
     * @param string $constraintType The type of violated constraint.
     *                               The possible values are
     *                               * FQCN, e.g. NotBlank::class
     *                               * short class name, e.g. "NotBlank"
     *                               * a human readable constraint type, e.g. "not blank" or "not_blank"
     */
    public function __construct(string $constraintType)
    {
        parent::__construct([]);
        $this->constraintType = $constraintType;
    }

    /**
     * @return string
     */
    public function getConstraintType(): string
    {
        return $this->constraintType;
    }
}
