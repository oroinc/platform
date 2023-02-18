<?php

namespace Oro\Bundle\ApiBundle\Form;

use Oro\Bundle\ApiBundle\Validator\Constraints\ConstraintWithStatusCodeInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraint;

/**
 * The validation constraint that is used to add named validation errors
 * without having own constraint class for each type of an error.
 * @internal Do not create an instance of this class directly,
 * use {@see \Oro\Bundle\ApiBundle\Form\FormUtil::addNamedFormError} instead.
 */
class NamedValidationConstraint extends Constraint implements ConstraintWithStatusCodeInterface
{
    private string $constraintType;
    private ?int $statusCode;

    /**
     * @param string   $constraintType The type of violated constraint.
     *                                 The possible values are
     *                                 * FQCN, e.g. NotBlank::class
     *                                 * short class name, e.g. "NotBlank"
     *                                 * a human readable constraint type, e.g. "not blank" or "not_blank"
     * @param int|null $statusCode     HTTP status code that should be returned if the constraint is not satisfied
     */
    public function __construct(string $constraintType, int $statusCode = null)
    {
        parent::__construct([]);
        $this->constraintType = $constraintType;
        $this->statusCode = $statusCode;
    }

    public function getConstraintType(): string
    {
        return $this->constraintType;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatusCode(): int
    {
        return $this->statusCode ?? Response::HTTP_BAD_REQUEST;
    }
}
