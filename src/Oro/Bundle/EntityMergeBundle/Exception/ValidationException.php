<?php

namespace Oro\Bundle\EntityMergeBundle\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends \Exception implements Exception
{
    /**
     * @var ConstraintViolationListInterface
     */
    protected $constraintViolations;

    /**
     * @param ConstraintViolationListInterface $constraintViolations
     */
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
