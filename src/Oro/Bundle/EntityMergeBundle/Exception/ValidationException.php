<?php

namespace Oro\Bundle\EntityMergeBundle\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Thrown when validation of entities or merge data fails during a merge operation.
 *
 * This exception encapsulates constraint violations that occurred during validation,
 * allowing callers to inspect the specific validation errors that prevented the merge
 * from proceeding.
 */
class ValidationException extends \Exception implements Exception
{
    /**
     * @var ConstraintViolationListInterface
     */
    protected $constraintViolations;

    public function __construct(ConstraintViolationListInterface $constraintViolations)
    {
        $this->constraintViolations = $constraintViolations;
    }

    /**
     * @return ConstraintViolationListInterface
     */
    public function getConstraintViolations()
    {
        return $this->constraintViolations;
    }
}
